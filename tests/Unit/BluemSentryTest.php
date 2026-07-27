<?php

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__, 2) . '/');
    }
}

namespace Unit {
    use Bluem\Wordpress\Observability\BluemSentry;
    use PHPUnit\Framework\TestCase;
    use Sentry\Event;
    use Sentry\Frame;
    use Sentry\Stacktrace;

    final class BluemSentryTest extends TestCase
    {
        public function testManualReportUsesOnlySafeAllowListedFields(): void
        {
            $safe = BluemSentry::sanitizeReportData([
                'service' => 'payments',
                'function' => 'payments_callback',
                'status' => 'Success',
                'error_report_id' => '20260726120000_123',
                'order_id' => 123,
                'transactionID' => 'transaction-secret',
                'entranceCode' => 'entrance-secret',
                'response' => ['email' => 'customer@example.com'],
                'production_accessToken' => 'secret-token',
            ]);

            self::assertSame([
                'component' => 'payments',
                'operation' => 'payments_callback',
                'status' => 'success',
                'error_report_id' => '20260726120000_123',
            ], $safe);
        }

        public function testUnsafeStatusIsCollapsedToUnknown(): void
        {
            self::assertSame(
                ['status' => 'unknown'],
                BluemSentry::sanitizeReportData(['status' => 'customer@example.com'])
            );
        }

        public function testSenderIdIsReadFromPluginSettingsForSentryTags(): void
        {
            self::assertStringContainsString("setTag('bluem_sender_id'", file_get_contents(
                dirname(__DIR__, 2) . '/src/Observability/BluemSentry.php'
            ));
        }

        public function testMessageRedactsPersonalAndCredentialLikeValues(): void
        {
            $message = BluemSentry::redactMessage(
                'Request failed for customer@example.com at https://example.test/callback?token=secret '
                . 'with Authorization: Bearer abc123 and order_id=12345. Transaction 98765.'
            );

            self::assertStringNotContainsString('customer@example.com', $message);
            self::assertStringNotContainsString('https://example.test', $message);
            self::assertStringNotContainsString('abc123', $message);
            self::assertStringNotContainsString('12345', $message);
            self::assertStringNotContainsString('98765', $message);
            self::assertStringContainsString('[email]', $message);
            self::assertStringContainsString('[url]', $message);
            self::assertStringContainsString('[secret]', $message);
            self::assertStringContainsString('[redacted]', $message);
        }

        public function testOnlyPluginAndBluemPhpFramesAreEligible(): void
        {
            $pluginRoot = dirname(__DIR__, 2);

            self::assertTrue(BluemSentry::isBluemFramePath($pluginRoot . '/bluem.php'));
            self::assertTrue(BluemSentry::isBluemFramePath($pluginRoot . '/vendor/bluem-development/bluem-php/src/Bluem.php'));
            self::assertFalse(BluemSentry::isBluemFramePath($pluginRoot . '/vendor/sentry/sentry/src/Client.php'));
            self::assertFalse(BluemSentry::isBluemFramePath('/var/www/other-plugin/plugin.php'));
        }

        public function testEventScrubberRemovesRequestDataAndAbsolutePaths(): void
        {
            $pluginRoot = dirname(__DIR__, 2);
            $event = Event::createEvent()
                ->setMessage('Failure for customer@example.com')
                ->setRequest([
                    'url' => 'https://shop.example.test/?order_id=123',
                    'data' => ['token' => 'secret'],
                ])
                ->setServerName('customer-server')
                ->setTags(['bluem_component' => 'payments', 'unsafe' => 'discard-me'])
                ->setStacktrace(new Stacktrace([
                    new Frame('process_payment', $pluginRoot . '/gateways/Bluem_Payment_Gateway.php', 123),
                ]));

            $sanitized = BluemSentry::sanitizeEvent($event);

            self::assertNotNull($sanitized);
            self::assertSame([], $sanitized->getRequest());
            self::assertSame('bluem-plugin', $sanitized->getServerName());
            self::assertSame(['bluem_component' => 'payments'], $sanitized->getTags());
            self::assertSame('bluem/gateways/Bluem_Payment_Gateway.php', $sanitized->getStacktrace()->getFrame(0)->getFile());
            self::assertStringNotContainsString('customer@example.com', $sanitized->getMessage());
        }
    }
}
