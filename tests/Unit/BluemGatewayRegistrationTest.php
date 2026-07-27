<?php

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__);
    }

    if (!function_exists('bluem_woocommerce_is_woocommerce_active')) {
        function bluem_woocommerce_is_woocommerce_active(): bool
        {
            return false;
        }
    }

    if (!function_exists('add_action')) {
        function add_action($hook, $callback): void
        {
        }
    }

    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void
        {
        }
    }
}

namespace Unit {
    use PHPUnit\Framework\TestCase;

    final class BluemGatewayRegistrationTest extends TestCase
    {
        public function testPaymentGatewayFilterRegistersEveryLegacyCheckoutGateway(): void
        {
            require_once __DIR__ . '/../../bluem-payments.php';

            $registered = bluem_add_gateway_class_payments(['Existing_Gateway']);

            self::assertSame(
                [
                    'Existing_Gateway',
                    'Bluem_iDEAL_Payment_Gateway',
                    'Bluem_PayPal_Payment_Gateway',
                    'Bluem_Creditcard_Payment_Gateway',
                    'Bluem_Sofort_Payment_Gateway',
                    'Bluem_CarteBancaire_Payment_Gateway',
                ],
                $registered
            );
        }

        public function testRegistrationDoesNotReplaceWooCommerceGateways(): void
        {
            require_once __DIR__ . '/../../bluem-payments.php';

            $registered = bluem_add_gateway_class_payments([
                'WC_BACS',
                'WC_COD',
            ]);

            self::assertSame('WC_BACS', $registered[0]);
            self::assertSame('WC_COD', $registered[1]);
            self::assertCount(7, $registered);
        }
    }
}
