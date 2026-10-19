<?php

use Bluem\Wordpress\Requests\BluemRequestLogRepository;

if (!defined('ABSPATH') || !function_exists('bluem_db_create_request')) {
    throw new RuntimeException('Bluem must be loaded before testing request log/link persistence.');
}

global $wpdb;
$originalUser = get_current_user_id();
$requestId = null;
$token = 'log-link-' . wp_generate_uuid4();
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

try {
    wp_set_current_user(0);
    $requestId = bluem_db_create_request([
        'user_id' => 0, 'type' => 'mandates', 'transaction_id' => $token,
        'entrance_code' => 'fixture', 'transaction_url' => '',
        'description' => 'Log/link integration fixture', 'debtor_reference' => $token,
        'payload' => '', 'order_id' => 987654,
    ]);
    $assert($requestId > 0, 'Request creation failed: ' . $wpdb->last_error);
    $request = bluem_db_get_request_by_id((string) $requestId);
    $assert($request !== false && $request->transaction_id === $token, 'Creation returned a link/log ID instead of the request ID.');
    $logs = bluem_db_get_logs_for_request($requestId);
    $assert(count($logs) === 1 && (int) $logs[0]->user_id === 0, 'Creation log or guest identity changed.');
    $links = bluem_db_get_links_for_request($requestId);
    $assert(count($links) === 1 && (int) $links[0]->item_id === 987654, 'Creation did not preserve the order association.');
    $linkId = bluem_db_create_link($requestId, 987654, 'custom');
    $assert($linkId > 0, 'Custom association failed.');
    $wpdb->update($wpdb->prefix . 'bluem_requests_links', ['timestamp' => '2000-01-01 00:00:00'], ['id' => $links[0]->id]);
    $wpdb->update($wpdb->prefix . 'bluem_requests_links', ['timestamp' => '2030-01-01 00:00:00'], ['id' => $linkId]);
    $links = bluem_db_get_links_for_request($requestId);
    $assert(count($links) === 2 && (int) $links[0]->id === $linkId, 'Link ordering changed.');
    $orderLinks = bluem_db_get_links_for_order(987654);
    $matching = array_filter($orderLinks, static fn($row) => (int) $row->request_id === $requestId);
    $assert(count($matching) === 1, 'Order lookup must exclude the custom association type.');

    $repository = new BluemRequestLogRepository($wpdb);
    $repository->append($requestId, 'older fixture', '2000-01-01 00:00:00', 0);
    $repository->append($requestId, 'newer fixture', '2030-01-01 00:00:00', 0);
    $logs = bluem_db_get_logs_for_request($requestId);
    $assert($logs[0]->description === 'newer fixture' && $logs[2]->description === 'older fixture', 'Log ordering changed.');
    $assert(bluem_db_update_request($requestId, ['status' => 'Pending']), 'Request update failed.');
    $logs = bluem_db_get_logs_for_request($requestId);
    $assert(count($logs) === 4, 'Successful update must add one log entry.');
    $updateLogs = array_filter($logs, static fn($row) => str_contains($row->description, 'Transaction updated.'));
    $assert(count($updateLogs) === 1, 'Update log message was lost.');
    $assert(!bluem_db_update_request($requestId, ['status' => 'Pending']), 'Unchanged update must still return false.');
    $assert(count(bluem_db_get_logs_for_request($requestId)) === 4, 'Unchanged update must not append a log.');
    $assert(bluem_db_get_links_for_request(-1) === [], 'Missing links must remain an empty list.');
    $assert(bluem_db_get_logs_for_request(-1) === [], 'Missing logs must remain an empty list.');
} finally {
    // The token also permits cleanup if an incorrect returned insert ID is detected.
    $ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM `' . $wpdb->prefix . 'bluem_requests` WHERE transaction_id = %s', $token));
    foreach ($ids as $id) {
        $wpdb->delete($wpdb->prefix . 'bluem_requests_log', ['request_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'bluem_requests_links', ['request_id' => $id]);
        $wpdb->delete($wpdb->prefix . 'bluem_requests', ['id' => $id]);
    }
    wp_set_current_user($originalUser);
}

WP_CLI::success('Bluem request log/link repositories preserve creation, lookup and update behavior.');
