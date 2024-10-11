<?php

namespace Bluem\Wordpress\Observability;

if (!defined('ABSPATH')) {
    exit;
}

use Exception;
use stdClass;

class BluemActivationNotifier
{


    private const NOTIFICATION_EMAIL = 'pluginsupport@bluem.nl';


    /**
     * Registration reporting email functionality
     */
    public function reportActivatedPlugin(): bool
    {
        $data = $this->createReportData();

        $author_name = 'Administratie van ' . esc_attr(get_bloginfo('name'));
        $author_email = esc_attr(
            get_option('admin_email')
        );

        $to = self::NOTIFICATION_EMAIL;

        $subject = '[' . get_bloginfo('name') . '] WordPress plug-in activation';

        $message = sprintf('<p>WordPress plug-in activation (door %s <%s>),</p>', $author_name, $author_email);
        $message .= sprintf('<p>Data:<br>%s</p>', $this->createStringFromData($data));
        $message .= sprintf('<p>Raw Data:<br>%s</p>', wp_json_encode($data));

        $message = nl2br($message);

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $mailing = wp_mail($to, $subject, $message, $headers);

        // if ($mailing) {
        // bluem_db_request_log(0, "Sent activation report mail to " . $to);
        // }
        //
        // if ($this->writeActivationFile($data)) {
        // bluem_db_request_log(0, "Written activation log file");
        // }

        return $mailing;
    }

    private function createReportData(): object
    {
        $bluem_options = get_option('bluem_woocommerce_options');
        $bluem_registration = get_option('bluem_woocommerce_registration');

        $dependency_bluem_php_version = bluem_get_composer_dependency_version('bluem-development/bluem-php') ?? 'unknown';

        $activation_report_id = sprintf('%s_%s', gmdate('Ymdhis'), random_int(0, 512));

        $data = new Stdclass();
        $data->activation_report_id = $activation_report_id;
        $data->{'Bluem SenderID'} = $bluem_options['senderID'] ?? '';
        $data->{'Website name'} = esc_attr(get_bloginfo('name'));
        $data->{'Website URL'} = esc_attr(get_bloginfo('url'));
        $data->{'Admin email'} = esc_attr(get_bloginfo('admin_email'));
        $data->{'Company name'} = isset($bluem_registration['company']['name']) ? $bluem_registration['company']['name'] : 'Company name onbekend';
        $data->{'Company telephone'} = isset($bluem_registration['company']['telephone']) ? $bluem_registration['company']['telephone'] : 'Company telephone onbekend';
        $data->{'Company email'} = isset($bluem_registration['company']['email']) ? $bluem_registration['company']['email'] : 'Company email onbekend';
        $data->{'Tech name'} = isset($bluem_registration['tech_contact']['name']) ? $bluem_registration['tech_contact']['name'] : 'Tech name onbekend';
        $data->{'Tech telephone'} = isset($bluem_registration['tech_contact']['telephone']) ? $bluem_registration['tech_contact']['telephone'] : 'Tech telephone onbekend';
        $data->{'Tech email'} = isset($bluem_registration['tech_contact']['email']) ? $bluem_registration['tech_contact']['email'] : 'Tech email onbekend';
        $data->{'WooCommerce version'} = class_exists('WooCommerce') ? WC()->version : esc_html__('WooCommerce not installed', 'bluem');
        $data->{'WordPress version'} = get_bloginfo('version');
        $data->{'Bluem PHP-library'} = $dependency_bluem_php_version;
        $data->{'Plug-in version'} = esc_attr($bluem_options['bluem_plugin_version'] ?? '0');
        $data->{'PHP version'} = PHP_VERSION;
        $data->{'Activation date'} = gmdate('Y-m-d H:i:s');
        $data->{'Activation IP'} = ''; // @todo: get IP?

        return $data;
    }

    private function createStringFromData(stdClass $data): string
    {
        ob_start();
        foreach ($data as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            bluem_render_obj_row_recursive(
                sprintf('<strong>%s</strong>', ucfirst($k)),
                $v
            );
        }
        return ob_get_clean() ?? '';
    }
}
