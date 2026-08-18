<?php

declare(strict_types=1);

namespace Bluem\Wordpress\Observability;

use Sentry\Event;
use Sentry\EventHint;
use Sentry\Frame;
use Sentry\Integration\EnvironmentIntegration;
use Sentry\Integration\FrameContextifierIntegration;
use Sentry\Integration\ModulesIntegration;
use Sentry\Integration\RequestIntegration;
use Sentry\Integration\TransactionIntegration;
use Sentry\State\Scope;
use Sentry\Severity;
use Sentry\Stacktrace;
use Throwable;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Privacy-conscious Sentry integration for Bluem errors.
 *
 * The DSN is an ingest-only public client key, not a Sentry API token. It can
 * be overridden or disabled in wp-config.php before the plugin is loaded:
 *
 * define('BLUEM_SENTRY_DSN', '');
 * define('BLUEM_SENTRY_ENABLED', false);
 */
final class BluemSentry
{
    private const DEFAULT_DSN = 'https://ce6a8fc06ff29a03f805eae2041fdd4e@o4506286009548800.ingest.us.sentry.io/4506286012891136';
    private const MAX_MESSAGE_LENGTH = 1000;
    private const MAX_CONTEXT_LENGTH = 160;

    private static bool $initialized = false;

    /**
     * Queue a production metric using Sentry's trace metrics API.
     *
     * Metrics remain buffered until the SDK flushes the current runtime
     * context, or until traceMetrics()->flush() is called explicitly.
     * Sentry failures must never affect plugin execution.
     *
     * @param 'counter'|'gauge'|'distribution' $type
     * @param int|float                         $value
     * @param array<string, int|float|string|bool> $attributes
     */
    public static function captureMetric(
        string $type,
        string $name,
        int|float $value,
        array $attributes = [],
        ?\Sentry\Unit $unit = null
    ): void {
        self::initialize();

        try {
            if (! function_exists('Sentry\\traceMetrics')) {
                return;
            }

            $metrics = \Sentry\traceMetrics();
            switch ($type) {
                case 'counter':
                    $metrics->count($name, $value, $attributes, $unit);
                    break;
                case 'gauge':
                    $metrics->gauge($name, $value, $attributes, $unit);
                    break;
                case 'distribution':
                    $metrics->distribution($name, $value, $attributes, $unit);
                    break;
            }
        } catch (Throwable) {
            // Observability must remain non-blocking for checkout and callbacks.
        }
    }

    /**
     * Initialize Sentry once, with only error-related integrations enabled.
     */
    public static function initialize(): void
    {
        if (self::$initialized || self::getDsn() === null) {
            return;
        }

        self::$initialized = true;

        try {
            \Sentry\init([
                'dsn' => self::getDsn(),
                'release' => 'bluem@' . self::getPluginVersion(),
                'environment' => self::getEnvironment(),
                'server_name' => 'bluem-plugin',
                'send_default_pii' => false,
                'max_request_body_size' => 'never',
                'attach_stacktrace' => true,
                'traces_sample_rate' => 0,
                'profiles_sample_rate' => 0,
                'before_send' => [self::class, 'sanitizeEvent'],
                'integrations' => static function (array $integrations): array {
                    return array_values(array_filter(
                        $integrations,
                        static function ($integration): bool {
                            return !(
                                $integration instanceof RequestIntegration
                                || $integration instanceof TransactionIntegration
                                || $integration instanceof FrameContextifierIntegration
                                || $integration instanceof EnvironmentIntegration
                                || $integration instanceof ModulesIntegration
                            );
                        }
                    ));
                },
            ]);
        } catch (Throwable $exception) {
            // Observability must never interfere with checkout or callbacks.
            self::$initialized = true;
        }
    }

    /**
     * Send a sanitized event for the existing Bluem support-report path.
     *
     * Raw report data is deliberately not passed to Sentry.
     *
     * @param array<string, mixed>|object $data
     */
    public static function captureReport($data): void
    {
        if (self::getDsn() === null) {
            return;
        }

        self::initialize();

        if (!self::$initialized || !function_exists('Sentry\\captureMessage')) {
            return;
        }

        $data = is_object($data) ? get_object_vars($data) : (array) $data;
        $safe = self::sanitizeReportData($data);
        $senderId = self::getSenderId();
        $messageValue = $data['message'] ?? 'Bluem error report';
        $message = self::redactMessage(is_scalar($messageValue) ? (string) $messageValue : 'Bluem error report');

        try {
            \Sentry\withScope(static function (Scope $scope) use ($safe, $message, $senderId): void {
                foreach (['component', 'operation', 'status'] as $tag) {
                    if (isset($safe[$tag])) {
                        $scope->setTag('bluem_' . $tag, $safe[$tag]);
                    }
                }

                if (!empty($safe['error_report_id'])) {
                    $scope->setTag('bluem_error_report_id', $safe['error_report_id']);
                }

                if ($senderId !== '') {
                    $scope->setTag('bluem_sender_id', $senderId);
                }

                $scope->setContext('bluem', $safe);
                \Sentry\captureMessage($message, Severity::error());
            });
        } catch (Throwable $exception) {
            // A failed telemetry request must never alter plugin behavior.
        }
    }

    private static function getSenderId(): string
    {
        if (!function_exists('get_option')) {
            return '';
        }

        $options = get_option('bluem_woocommerce_options', []);
        if (!is_array($options) || !isset($options['senderID']) || !is_scalar($options['senderID'])) {
            return '';
        }

        return self::sanitizeIdentifier((string) $options['senderID']);
    }

    /**
     * Return the exact fields allowed into a manual Sentry context.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    public static function sanitizeReportData(array $data): array
    {
        $safe = [];

        $fieldMap = [
            'service' => 'component',
            'function' => 'operation',
            'status' => 'status',
            'error_report_id' => 'error_report_id',
        ];

        foreach ($fieldMap as $source => $target) {
            if (!isset($data[$source]) || !is_scalar($data[$source])) {
                continue;
            }

            $value = $target === 'status'
                ? self::sanitizeStatus((string) $data[$source])
                : self::sanitizeIdentifier((string) $data[$source]);
            if ($value !== '') {
                $safe[$target] = $value;
            }
        }

        return $safe;
    }

    /**
     * Redact common personal, credential, URL, and payment-like values.
     */
    public static function redactMessage(string $message): string
    {
        $message = function_exists('wp_strip_all_tags') ? wp_strip_all_tags($message) : strip_tags($message);
        $message = preg_replace('/https?:\\/\\/\\S+/i', '[url]', $message) ?? $message;
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}/i', '[email]', $message) ?? $message;
        $message = preg_replace('/\\b(?:authorization[=: ]+(?:bearer\\s+)?|bearer\\s+|(?:access|api)[_-]?token[=: ]+)[^\\s,;]+/i', '[secret]', $message) ?? $message;
        $message = preg_replace('/\\b(?:iban|account|card|order|transaction|entrance|mandate)[_-]?(?:id|code|number)?[=: ]+[^\\s,;]+/i', '[redacted]', $message) ?? $message;
        $message = preg_replace('/\\b(?:order|transaction|entrance|mandate)(?:\\s+(?:id|code|number))?\\s*#?\\s*[A-Z0-9_-]*\\d[A-Z0-9_-]*/i', '[redacted]', $message) ?? $message;

        $message = preg_replace('/\\s+/', ' ', trim($message)) ?? trim($message);

        return function_exists('mb_substr')
            ? mb_substr($message, 0, self::MAX_MESSAGE_LENGTH)
            : substr($message, 0, self::MAX_MESSAGE_LENGTH);
    }

    /**
     * Final event-level privacy and scope filter.
     */
    public static function sanitizeEvent(Event $event, ?EventHint $hint = null): ?Event
    {
        if (!self::eventContainsBluemFrame($event)) {
            return null;
        }

        $event->setRequest([]);
        $event->setUser(null);
        $event->setBreadcrumb([]);
        $event->setServerName('bluem-plugin');
        $event->setModules([]);
        $event->setOsContext(null);
        $event->setRuntimeContext(null);
        $event->setTags(self::sanitizeTags($event->getTags()));

        if ($event->getMessage() !== null) {
            $event->setMessage(self::redactMessage($event->getMessage()));
        }

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(self::redactMessage($exception->getValue()));

            $stacktrace = self::sanitizeStacktrace($exception->getStacktrace());
            if ($stacktrace !== null) {
                $exception->setStacktrace($stacktrace);
            }
        }

        $event->setStacktrace(self::sanitizeStacktrace($event->getStacktrace()));

        return $event;
    }

    public static function isBluemFramePath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);
        $pluginPath = rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/') . '/';

        if (!str_starts_with($path, $pluginPath)) {
            return false;
        }

        // Sentry's own frames must not make unrelated events look like Bluem errors.
        if (str_starts_with($path, $pluginPath . 'vendor/sentry/')) {
            return false;
        }

        return true;
    }

    private static function eventContainsBluemFrame(Event $event): bool
    {
        foreach ($event->getExceptions() as $exception) {
            $stacktrace = $exception->getStacktrace();
            if ($stacktrace === null) {
                continue;
            }

            foreach ($stacktrace->getFrames() as $frame) {
                if (self::isBluemFramePath($frame->getFile())) {
                    return true;
                }
            }
        }

        $stacktrace = $event->getStacktrace();
        if ($stacktrace !== null) {
            foreach ($stacktrace->getFrames() as $frame) {
                if (self::isBluemFramePath($frame->getFile())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Keep function and line information while replacing absolute filesystem paths.
     */
    private static function sanitizeStacktrace(?Stacktrace $stacktrace): ?Stacktrace
    {
        if ($stacktrace === null) {
            return null;
        }

        $frames = [];
        foreach ($stacktrace->getFrames() as $frame) {
            $frames[] = new Frame(
                $frame->getFunctionName(),
                self::safeFramePath($frame->getFile()),
                $frame->getLine(),
                $frame->getRawFunctionName(),
                null,
                [],
                $frame->isInApp()
            );
        }

        return empty($frames) ? null : new Stacktrace($frames);
    }

    /**
     * Keep only the tags created by this integration.
     *
     * @param array<string, string> $tags
     * @return array<string, string>
     */
    private static function sanitizeTags(array $tags): array
    {
        $safe = [];
        foreach ($tags as $key => $value) {
            if (str_starts_with($key, 'bluem_')) {
                $safe[$key] = self::sanitizeIdentifier($value);
            }
        }

        return $safe;
    }

    private static function safeFramePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $pluginPath = rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/') . '/';

        if (str_starts_with($path, $pluginPath)) {
            return 'bluem/' . ltrim(substr($path, strlen($pluginPath)), '/');
        }

        return '[external]';
    }

    private static function sanitizeIdentifier(string $value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_.:-]/', '_', $value) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($value, 0, self::MAX_CONTEXT_LENGTH)
            : substr($value, 0, self::MAX_CONTEXT_LENGTH);
    }

    private static function sanitizeStatus(string $value): string
    {
        $value = strtolower(self::sanitizeIdentifier($value));
        $allowed = [
            'success',
            'successmanual',
            'failure',
            'failed',
            'cancelled',
            'expired',
            'new',
            'open',
            'pending',
            'bankselected',
            'refunded',
            'unknown',
        ];

        return in_array($value, $allowed, true) ? $value : 'unknown';
    }

    private static function getDsn(): ?string
    {
        if (defined('BLUEM_SENTRY_ENABLED') && BLUEM_SENTRY_ENABLED === false) {
            return null;
        }

        $dsn = defined('BLUEM_SENTRY_DSN') ? BLUEM_SENTRY_DSN : self::DEFAULT_DSN;

        return is_string($dsn) && trim($dsn) !== '' ? trim($dsn) : null;
    }

    private static function getPluginVersion(): string
    {
        return defined('BLUEM_PLUGIN_VERSION') ? BLUEM_PLUGIN_VERSION : 'unknown';
    }

    private static function getEnvironment(): string
    {
        if (defined('WP_ENVIRONMENT_TYPE') && is_string(WP_ENVIRONMENT_TYPE)) {
            return self::sanitizeIdentifier(WP_ENVIRONMENT_TYPE) ?: 'production';
        }

        return 'production';
    }
}
