<?php

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__, 2) . '/');
    }

    if (! function_exists('__')) {
        function __($text, $domain = 'default')
        {
            return $text;
        }
    }

    require_once dirname(__DIR__, 2) . '/bluem-interface.php';
}

namespace Unit {
    use PHPUnit\Framework\TestCase;

    final class BluemRequestPresentationTest extends TestCase
    {
        public function testRequestTypeIsPresentedAsAReadablePaymentLabel(): void
        {
            foreach ([
                'ideal' => ['ideal', 'iDEAL'],
                'credit card' => ['creditcard', 'Credit card'],
                'paypal' => ['paypal', 'PayPal'],
                'mandate' => ['mandates', 'eMandate'],
                'legacy payment' => ['payments', 'Payment'],
                'identity' => ['identity', 'iDIN'],
            ] as [$type, $label]) {
                self::assertSame($label, \bluem_get_request_type_label($type));
            }
        }

        public function testUnknownAndMissingTypesHaveSafeFallbacks(): void
        {
            self::assertSame('Legacytype', \bluem_get_request_type_label('legacytype'));
            self::assertSame('Unknown', \bluem_get_request_type_label(null));
        }
    }
}
