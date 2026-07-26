<?php

namespace Bluem\Wordpress\Payments;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!class_exists(AbstractPaymentMethodType::class)) {
    return;
}

final class BluemPaymentMethodType extends AbstractPaymentMethodType
{
    protected $name;

    private $gateway;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function initialize()
    {
        $this->settings = get_option('woocommerce_' . $this->name . '_settings', []);

        $this->gateway = null;
        if (function_exists('WC') && WC()->payment_gateways()) {
            $gateways = WC()->payment_gateways()->payment_gateways;
            $this->gateway = $gateways[$this->name] ?? null;
        }
    }

    public function is_active()
    {
        return 'yes' === $this->get_setting('enabled', 'no')
            && null !== $this->gateway
            && 'yes' === ($this->gateway->enabled ?? 'no');
    }

    public function get_payment_method_script_handles()
    {
        $script_path = dirname(__DIR__, 2) . '/js/bluem_woocommerce_blocks_payment_methods.js';

        wp_register_script(
            'bluem-woocommerce-blocks-payment-methods',
            plugins_url(
                'js/bluem_woocommerce_blocks_payment_methods.js',
                dirname(__DIR__, 2) . '/bluem.php'
            ),
            ['wp-element', 'wc-blocks-registry', 'wc-settings'],
            file_exists($script_path) ? (string) filemtime($script_path) : '1.0.0',
            true
        );

        return ['bluem-woocommerce-blocks-payment-methods'];
    }

    public function get_payment_method_data()
    {
        $data = [
            'title' => $this->get_setting('title', $this->name),
            'description' => $this->get_setting('description', ''),
            'supports' => ['products'],
        ];

        if ($this->gateway && method_exists($this->gateway, 'get_block_payment_method_data')) {
            $data = array_merge($data, $this->gateway->get_block_payment_method_data());
        }

        return $data;
    }
}
