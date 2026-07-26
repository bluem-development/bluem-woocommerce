<?php

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', __DIR__);
    }

    if (!class_exists('WC_Payment_Gateway')) {
        abstract class WC_Payment_Gateway
        {
        }
    }
}

namespace Unit {
    use PHPUnit\Framework\TestCase;

    final class BluemGatewayLoadingTest extends TestCase
    {
        public function testAllBankBasedGatewaysCanBeLoaded(): void
        {
            $gateways = [
                'Bluem_iDEAL_Payment_Gateway.php' => 'Bluem_iDEAL_Payment_Gateway',
                'Bluem_PayPal_Payment_Gateway.php' => 'Bluem_PayPal_Payment_Gateway',
                'Bluem_Creditcard_Payment_Gateway.php' => 'Bluem_Creditcard_Payment_Gateway',
                'Bluem_Sofort_Payment_Gateway.php' => 'Bluem_Sofort_Payment_Gateway',
                'Bluem_CarteBancaire_Payment_Gateway.php' => 'Bluem_CarteBancaire_Payment_Gateway',
            ];

            foreach ($gateways as $file => $class) {
                require_once __DIR__ . '/../../gateways/' . $file;
                self::assertTrue(class_exists($class), "Gateway class {$class} was not loaded.");
            }
        }
    }
}
