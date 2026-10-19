<?php
// Isolated WordPress boundary doubles; API response classes remain real.
define('ABSPATH', dirname(__DIR__, 2) . '/');
$GLOBALS['current_user'] = (object) ['ID' => 7, 'user_nicename' => 'member'];
$GLOBALS['mandate_options'] = ['bluem_woocommerce_options' => ['mandate_id_counter' => 40]];
$GLOBALS['mandate_storage'] = [];
$GLOBALS['mandate_reports'] = [];
$GLOBALS['mandate_updates'] = [];
$GLOBALS['mandate_config'] = (object) ['sequenceType' => 'OOFF', 'eMandateReason' => '', 'thanksPageURL' => '/thanks', 'debtorReferenceFieldName' => ''];
$_REQUEST = ['_wpnonce' => 'valid'];
$_POST = ['bluem-submitted' => '1'];
$_GET = ['mandateID' => '41'];
final class MandateTestStop extends RuntimeException {}
function add_shortcode(...$args) {}
function add_filter(...$args) {}
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid'; }
function wp_unslash($value) { return stripslashes($value); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES); }
function esc_html__($value, $domain = '') { return esc_html($value); }
function esc_attr($value) { return esc_html($value); }
function wp_kses_post($value) { return $value; }
function get_current_user_id() { return (int) ($GLOBALS['current_user']->ID ?? 0); }
function is_user_logged_in() { return get_current_user_id() > 0; }
function current_user_can(...$args) { return is_user_logged_in(); }
function get_option($key) { return $GLOBALS['mandate_options'][$key] ?? false; }
function update_option($key, $value) { $GLOBALS['mandate_options'][$key] = $value; }
function update_user_meta(...$args) {}
function get_user_meta(...$args) { return ''; }
function home_url($path) { return 'https://shop.invalid' . $path; }
function wp_redirect($url) { throw new MandateTestStop($url); }
function bluem_woocommerce_route_url($path) { return home_url('/' . $path); }
function bluem_woocommerce_get_config() { return $GLOBALS['mandate_config']; }
function bluem_db_get_storage() { return $GLOBALS['mandate_storage']; }
function bluem_db_insert_storage($data) { $GLOBALS['mandate_storage'] = array_merge($GLOBALS['mandate_storage'], $data); }
function bluem_db_get_requests_by_keyvalues($query) { return []; }
function bluem_error_report_email($data) { $GLOBALS['mandate_reports'][] = $data; return true; }
function bluem_dialogs_render_prompt($message) { throw new MandateTestStop($message); }
function bluem_db_create_request($data) { $GLOBALS['mandate_created'] = $data; return $GLOBALS['mandate_insert_result'] ?? 1; }
function wp_json_encode($data) { return json_encode($data); }
function bluem_db_get_request_by_transaction_id_and_type(...$args) { return $GLOBALS['mandate_record'] ?? false; }
function bluem_db_update_request($id, $data) { $GLOBALS['mandate_updates'][] = $data; }
function bluem_transaction_notification_email($id) {
    $GLOBALS['mandate_notification_updates'] = $GLOBALS['mandate_updates'];
}
function bluem_woocommerce_create_client($config, $diagnostics = null) {
    if (isset($GLOBALS['mandate_client_exception'])) { throw $GLOBALS['mandate_client_exception']; }
    return new class {
        public function CreateMandateRequest($debtor, $user, $id) {
            $GLOBALS['mandate_arguments'] = [$debtor, $user, $id];
            return (object) ['mandateID' => (string) $id, 'entranceCode' => 'private-entrance'];
        }
        public function PerformRequest($request) { return $GLOBALS['mandate_response']; }
        public function MandateStatus($id, $entrance) { return $GLOBALS['mandate_response']; }
        public function getConfig($key) { return 'test'; }
    };
}
require dirname(__DIR__, 2) . '/bluem-mandates-shortcode.php';
require dirname(__DIR__, 2) . '/bluem-interface.php';
