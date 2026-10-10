<?php

use Bluem\Wordpress\Requests\BluemRequestRepository;

if (!defined('ABSPATH') || !function_exists('bluem_db_get_request_by_id')) {
    throw new RuntimeException('Bluem must be loaded before testing request lookup.');
}

global $wpdb;
$repository = new BluemRequestRepository($wpdb);
$table = $wpdb->prefix . 'bluem_requests';
$token = 'lookup-' . wp_generate_uuid4();
$ids = [];
$originalUser = get_current_user_id();
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    // Older correlated records must still be selected when newer unrelated
    // requests exist. These are Bluem request rows, not WooCommerce orders.
    foreach ([
        [0, 'mandates', $token, 'code-a', '2026-01-01 00:00:00'],
        [0, 'identity', $token, 'code-b', '2026-01-02 00:00:00'],
        [0, 'mandates', $token . '-new', 'code-c', '2026-01-03 00:00:00'],
        [42, 'mandates', $token . '-other', 'code-d', '2026-01-04 00:00:00'],
    ] as $index => [$userId, $type, $transaction, $entrance, $timestamp]) {
        $inserted = $wpdb->insert($table, [
            'user_id' => $userId, 'type' => $type, 'transaction_id' => $transaction,
            'entrance_code' => $entrance, 'timestamp' => $timestamp,
            'debtor_reference' => $token . '-' . $index,
            'transaction_url' => '', 'description' => 'Lookup integration fixture', 'payload' => '',
        ]);
        $assert($inserted !== false, 'Unable to insert lookup fixture: ' . $wpdb->last_error);
        $ids[] = (int) $wpdb->insert_id;
    }

    wp_set_current_user(0);
    $assert((int) bluem_db_get_request_by_id((string) $ids[0])->id === $ids[0], 'ID lookup failed.');
    $assert((int) bluem_db_get_request_by_debtor_reference($token . '-0')->id === $ids[0], 'Debtor lookup failed.');
    $assert((int) bluem_db_get_request_by_transaction_id($token . '-new')->id === $ids[2], 'Transaction lookup failed.');
    $assert((int) bluem_db_get_request_by_transaction_id_and_type($token, 'mandates')->id === $ids[0], 'Transaction/type correlation failed.');
    $assert((int) bluem_db_get_request_by_transaction_id_and_entrance_code($token, 'code-b')->id === $ids[1], 'Entrance-code correlation failed.');
    $assert((int) bluem_db_get_most_recent_request()->id === $ids[2], 'Most-recent mandate lookup failed.');
    $assert((int) bluem_db_get_most_recent_request(42)->id === $ids[3], 'Explicit-user lookup failed.');
    $assert(count(bluem_db_get_requests_by_user_id()) === 3, 'Current-user default failed.');
    $assert(count(bluem_db_get_requests_by_user_id_and_type(null, 'mandates')) === 2, 'User/type filtering failed.');
    $rows = bluem_db_get_requests_by_keyvalue('user_id', 0, 'timestamp', 'DESC', 2);
    $assert(array_map(static fn($row) => (int) $row->id, $rows) === [$ids[2], $ids[1]], 'Ordering/limit failed.');
    $assert($repository->findByTransactionIdAndEntranceCode($token, 'missing') === false, 'Missing correlation must return false.');
    $assert($repository->findForUser(987654) === [], 'Missing user must return an empty list.');
    $assert($repository->findMostRecent(0, 'ideal') === false, 'Legacy most-recent type allowlist changed.');
} finally {
    foreach ($ids as $id) {
        $wpdb->delete($table, ['id' => $id]);
    }
    wp_set_current_user($originalUser);
}

WP_CLI::success('Bluem request repository and procedural lookup adapters passed against WordPress wpdb.');
