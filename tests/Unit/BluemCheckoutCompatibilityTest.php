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

namespace Automattic\WooCommerce\Utilities {
    final class OrderUtil
    {
        public static bool $custom_orders_table_enabled = true;

        public static function custom_orders_table_usage_is_enabled(): bool
        {
            return self::$custom_orders_table_enabled;
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
            \Automattic\WooCommerce\Utilities\OrderUtil::$custom_orders_table_enabled = true;
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

        public function testLegacyOrderStorageKeepsCustomCorrelationArguments(): void
        {
            \Automattic\WooCommerce\Utilities\OrderUtil::$custom_orders_table_enabled = false;

            $arguments = [
                'bluem_mandateid' => 'mandate-123',
            ];

            self::assertSame($arguments, BluemOrderQuery::mapHposArgs($arguments));
        }

        public function testNativeMetadataCorrelationUsesTheActiveOrderDatastore(): void
        {
            self::assertSame([
                'meta_query' => [
                    ['key' => 'bluem_mandateid', 'value' => 'mandate-123'],
                ],
            ], BluemOrderQuery::metadataEquals('bluem_mandateid', 'mandate-123'));

            \Automattic\WooCommerce\Utilities\OrderUtil::$custom_orders_table_enabled = false;

            self::assertSame([
                'meta_key' => 'bluem_mandateid',
                'meta_value' => 'mandate-123',
            ], BluemOrderQuery::metadataEquals('bluem_mandateid', 'mandate-123'));
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
                    7 => (object) [
                        'id' => 'bluem_payments_ideal',
                        'enabled' => 'yes',
                    ],
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

        public function testBluemPaymentSchemaIsWellFormed(): void
        {
            $schema = __DIR__ . '/../../vendor/bluem-development/bluem-php/validation/EPayment.xsd';
            $document = new \DOMDocument();

            self::assertFileExists($schema);
            self::assertTrue(
                $document->load($schema),
                'The Bluem ePayment XSD must remain well-formed XML.'
            );
        }

        public function testBluemCanValidateAGeneratedPaymentRequest(): void
        {
            $bluem = new \Bluem\BluemPHP\Bluem((object) [
                'environment' => 'test',
                'senderID' => 'S12345',
                'brandID' => 'BLUEM_BRANDID',
                'test_accessToken' => 'BLUEM_TEST_ACCESS_TOKEN',
                'IDINBrandID' => 'BLUEM_BRANDID',
                'merchantID' => 'BLUEM_MERCHANTID',
                'merchantReturnURLBase' => 'BLUEM_MERCHANTRETURNURLBASE',
                'production_accessToken' => '',
                'expectedReturnStatus' => 'success',
                'eMandateReason' => 'eMandateReason',
                'sequenceType' => 'OOFF',
                'localInstrumentCode' => 'B2B',
            ]);
            $request = $bluem->CreatePaymentRequest(
                'Payment test',
                'order123',
                12.34,
                null,
                'EUR',
                null,
                'https://example.test/return'
            );
            $validator = new \Bluem\BluemPHP\Validators\BluemXMLValidator();

            self::assertTrue(
                $validator->validate($request->RequestContext(), $request->XmlString()),
                implode('; ', $validator->errorDetails ?? [])
            );
        }
    }
}
