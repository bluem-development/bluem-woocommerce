<?php

namespace Bluem\Wordpress\Settings;

/**
 * Core field definitions. Translated/escaped display labels come from the WP adapter.
 */
final class BluemCoreOptions
{
    /** @param array<string, string> $labels */
    public function __construct(private readonly array $labels)
    {
    }

    public function get(): array
    {
        return [
            'environment' => [
                'key' => 'environment',
                'title' => 'bluem_environment',
                'name' => $this->labels['environment_name'],
                'description' => $this->labels['environment_description'],
                'type' => 'select',
                'default' => 'test',
                'options' => [
                    'test' => 'Test',
                    'prod' => "Production (live)",
                ],
            ],
            'senderID' => [
                'key' => 'senderID',
                'title' => 'bluem_senderID',
                'name' => $this->labels['senderID_name'],
                'description' => $this->labels['senderID_description'],
                'default' => "",
            ],
            'test_accessToken' => [
                'key' => 'test_accessToken',
                'title' => 'bluem_test_accessToken',
                'type' => 'password',
                'name' => $this->labels['test_accessToken_name'],
                'description' => $this->labels['test_accessToken_description'],
                'default' => '',
            ],
            'production_accessToken' => [
                'key' => 'production_accessToken',
                'title' => 'bluem_production_accessToken',
                'type' => 'password',
                'name' => $this->labels['production_accessToken_name'],
                'description' => $this->labels['production_accessToken_description'],
                'default' => '',
            ],
            'expectedReturnStatus' => [
                'key' => 'expectedReturnStatus',
                'title' => 'bluem_expectedReturnStatus',
                'name' => $this->labels['expectedReturnStatus_name'],
                'description' => $this->labels['expectedReturnStatus_description'],
                'default' => 'success',
                'type' => 'select',
                'options' => [
                    'success' => 'success',
                    'cancelled' => 'cancelled',
                    'expired' => 'expired',
                    'failure' => 'failure',
                    'open' => 'open',
                    'pending' => 'pending',
                    'none' => 'none',
                ],
            ],
            'suppress_woo' => [
                'key' => 'suppress_woo',
                'title' => 'bluem_suppress_woo',
                'name' => $this->labels['suppress_woo_name'],
                'description' => $this->labels['suppress_woo_description'],
                'type' => 'select',
                'default' => '0',
                'options' => [
                    '0' => "Use WooCommerce",
                    '1' => 'Do NOT use WooCommerce',
                ],
            ],
            'error_reporting_email' => [
                'key' => 'error_reporting_email',
                'title' => 'bluem_error_reporting_email',
                'name' => $this->labels['error_reporting_email_name'],
                'description' => $this->labels['error_reporting_email_description'],
                'type' => 'select',
                'default' => '1',
                'options' => [
                    '1' => $this->labels['error_reporting_email_1'],
                    '0' => $this->labels['error_reporting_email_0'],
                ],
            ],
            'transaction_notification_email' => [
                'key' => 'transaction_notification_email',
                'title' => 'bluem_transaction_notification_email',
                'name' => $this->labels['transaction_notification_email_name'],
                'description' => "Specify here whether you, as the website owner, want to automatically receive a notification email with transaction details",
                'type' => 'select',
                'default' => '0',
                'options' => [
                    '0' => $this->labels['transaction_notification_email_0'],
                    '1' => $this->labels['transaction_notification_email_1'],
                ],
            ],
        ];
    }
}
