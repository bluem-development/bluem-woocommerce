<?php

namespace Automattic\WooCommerce\Blocks\Payments\Integrations {
    abstract class AbstractPaymentMethodType
    {
        protected $settings = [];

        protected function get_setting($key, $default = null)
        {
            return $this->settings[$key] ?? $default;
        }
    }
}

namespace {
    if (!function_exists('get_option')) {
        function get_option($key, $default = false)
        {
            return $GLOBALS['bluem_test_options'][$key] ?? $default;
        }
    }

    if (!function_exists('WC')) {
        function WC()
        {
            return $GLOBALS['bluem_test_woocommerce'];
        }
    }

    if (!function_exists('wp_register_script')) {
        function wp_register_script($handle, $src, $dependencies, $version, $in_footer)
        {
            $GLOBALS['bluem_test_registered_script'] = compact(
                'handle',
                'src',
                'dependencies',
                'version',
                'in_footer'
            );
        }
    }

    if (!function_exists('plugins_url')) {
        function plugins_url($path, $plugin = '')
        {
            return '/plugins/bluem/' . ltrim($path, '/');
        }
    }

    if (!function_exists('esc_attr')) {
        function esc_attr($value)
        {
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }

    final class BluemTestPaymentGateways
    {
        public array $payment_gateways;

        public function __construct(array $payment_gateways)
        {
            $this->payment_gateways = $payment_gateways;
        }
    }

    final class BluemTestWooCommerce
    {
        private BluemTestPaymentGateways $gateways;

        public function __construct(BluemTestPaymentGateways $gateways)
        {
            $this->gateways = $gateways;
        }

        public function payment_gateways(): BluemTestPaymentGateways
        {
            return $this->gateways;
        }
    }
}

namespace Unit {
    use Bluem\Wordpress\Payments\BluemOrderQuery;
    use Bluem\Wordpress\Payments\BluemPaymentMethodType;
    use PHPUnit\Framework\TestCase;

    final class BluemCheckoutCompatibilityTest extends TestCase
    {
        protected function setUp(): void
        {
            $GLOBALS['bluem_test_options'] = [];
            $GLOBALS['bluem_test_registered_script'] = null;
            $GLOBALS['bluem_test_woocommerce'] = new \BluemTestWooCommerce(
                new \BluemTestPaymentGateways([])
            );
        }

        public function testHposQueryMappingSupportsPaymentAndMandateCorrelation(): void
        {
            $result = BluemOrderQuery::mapHposArgs([
                'bluem_transactionid' => 'tx-123',
                'bluem_entrancecode' => 'entrance-123',
                'bluem_mandateid' => 'mandate-123',
                'meta_query' => [
                    ['key' => 'existing', 'value' => 'value'],
                ],
            ]);

            self::assertArrayNotHasKey('bluem_transactionid', $result);
            self::assertArrayNotHasKey('bluem_entrancecode', $result);
            self::assertArrayNotHasKey('bluem_mandateid', $result);
            self::assertCount(4, $result['meta_query']);
            self::assertSame('bluem_transactionid', $result['meta_query'][1]['key']);
            self::assertSame('bluem_entrancecode', $result['meta_query'][2]['key']);
            self::assertSame('bluem_mandateid', $result['meta_query'][3]['key']);
        }

        public function testBlocksPaymentMethodReadsGatewaySettingsAndRegistersItsScript(): void
        {
            $GLOBALS['bluem_test_options']['woocommerce_bluem_payments_ideal_settings'] = [
                'enabled' => 'yes',
                'title' => 'Bluem iDEAL',
                'description' => 'Pay with iDEAL',
            ];
            $GLOBALS['bluem_test_woocommerce'] = new \BluemTestWooCommerce(
                new \BluemTestPaymentGateways([
                    'bluem_payments_ideal' => (object) ['enabled' => 'yes'],
                ])
            );

            $integration = new BluemPaymentMethodType('bluem_payments_ideal');
            $integration->initialize();

            self::assertTrue($integration->is_active());
            self::assertSame(
                [
                    'title' => 'Bluem iDEAL',
                    'description' => 'Pay with iDEAL',
                    'supports' => ['products'],
                ],
                $integration->get_payment_method_data()
            );
            self::assertSame(
                ['bluem-woocommerce-blocks-payment-methods'],
                $integration->get_payment_method_script_handles()
            );
            self::assertSame(
                ['wp-element', 'wc-blocks-registry', 'wc-settings'],
                $GLOBALS['bluem_test_registered_script']['dependencies']
            );
        }

        public function testBlocksPaymentMethodIsInactiveWhenGatewayWasNotLoaded(): void
        {
            $GLOBALS['bluem_test_options']['woocommerce_bluem_payments_ideal_settings'] = [
                'enabled' => 'yes',
            ];

            $integration = new BluemPaymentMethodType('bluem_payments_ideal');
            $integration->initialize();

            self::assertFalse($integration->is_active());
        }

        public function testBlocksAssetRegistersAllCheckoutGatewayIdentifiers(): void
        {
            $script = file_get_contents(__DIR__ . '/../../js/bluem_woocommerce_blocks_payment_methods.js');

            self::assertIsString($script);
            self::assertStringContainsString('registerPaymentMethod', $script);
            foreach ([
                'bluem_payments_ideal',
                'bluem_payments_paypal',
                'bluem_payments_creditcard',
                'bluem_payments_sofort',
                'bluem_payments_cartebancaire',
                'bluem_mandates',
            ] as $gateway_id) {
                self::assertStringContainsString("'{$gateway_id}'", $script);
            }
        }
    }
}
