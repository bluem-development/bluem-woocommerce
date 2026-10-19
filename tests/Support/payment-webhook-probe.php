<?php

// Runs the exiting HTTP adapter in a disposable CLI process, with no live API.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require __DIR__ . '/PaymentTransitionBootstrap.php';

function wc_get_orders($query) {
    $GLOBALS['probe_query'] = $query;
    return [$GLOBALS['probe_order']];
}
function get_user_meta($id) {
    $GLOBALS['probe_order']->events[] = ['get_user_meta', $id];
    return [];
}

$order = new class($argv[2]) {
    public array $events = [];
    public function __construct(public string $status) {}
    public function get_status() { $this->events[] = ['get_status']; return $this->status; }
    public function get_user_id() { $this->events[] = ['get_user_id']; return 7; }
    public function update_status($status, $note) {
        $this->events[] = ['update_status', $status, $note];
        $this->status = $status;
    }
    public function add_order_note($note) { $this->events[] = ['add_order_note', $note]; }
};
$GLOBALS['probe_order'] = $order;
$gateway = new class($argv[1], $argv[3] ?? 'entrance') extends Bluem_Bank_Based_Payment_Gateway {
    public function __construct($status, $entrance) {
        $this->bluem = new class($status, $entrance) {
            public function __construct(private string $status, private string $entrance) {}
            public function Webhook() {
                return new class($this->status, $this->entrance) {
                    public object $xmlObject;
                    public function __construct(private string $status, private string $entrance) { $this->xmlObject = new stdClass(); }
                    public function getStatus() { return $this->status; }
                    public function getEntranceCode() { return $this->entrance; }
                    public function getTransactionID() { return 'fixture-transaction'; }
                };
            }
        };
    }
};
ob_start();
register_shutdown_function(static function () use ($order) {
    $body = ob_get_clean();
    echo json_encode([
        'body' => $body, 'http' => http_response_code(), 'events' => $order->events,
        'status' => $order->status, 'query' => $GLOBALS['probe_query'] ?? null,
    ], JSON_THROW_ON_ERROR);
});
$gateway->bluem_bank_payments_webhook();
