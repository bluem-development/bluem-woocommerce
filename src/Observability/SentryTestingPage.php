<?php

namespace Bluem\Wordpress\Observability;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SentryTestingPage {
    public static function register(): void {
        add_submenu_page(
            'bluem-admin',
            esc_html__( 'Sentry test', 'bluem' ),
            esc_html__( 'Sentry test', 'bluem' ),
            'manage_options',
            'bluem-sentry-test',
            [ self::class, 'render' ]
        );
    }

    public static function render(): void {
        if ( ! self::available() ) {
            wp_die( esc_html__( 'This diagnostic page is only available on localhost or while WP_DEBUG is enabled.', 'bluem' ) );
        }
        $message = '';
        if ( isset( $_POST['bluem_sentry_test'] ) ) {
            check_admin_referer( 'bluem_sentry_test' );
            $type = sanitize_key( wp_unslash( $_POST['bluem_sentry_test'] ) );
            $message = 'metric' === $type ? BluemSentry::sendTestMetric() : ( 'dsn' === $type ? BluemSentry::verifyDsnAndLogs() : BluemSentry::sendTestEvent() );
        }
        ?>
        <div class="wrap"><h1><?php esc_html_e( 'Sentry test', 'bluem' ); ?></h1>
        <p><?php esc_html_e( 'Send a diagnostic event or metric to the configured Sentry project.', 'bluem' ); ?></p>
        <?php if ( $message ) : ?><div class="notice notice-info"><p><?php echo esc_html( $message ); ?></p></div><?php endif; ?>
        <form method="post"><?php wp_nonce_field( 'bluem_sentry_test' ); ?>
            <p><button class="button button-primary" name="bluem_sentry_test" value="dsn"><?php esc_html_e( 'Verify DSN and logs', 'bluem' ); ?></button>
            <button class="button" name="bluem_sentry_test" value="event"><?php esc_html_e( 'Send test event', 'bluem' ); ?></button>
            <button class="button" name="bluem_sentry_test" value="metric"><?php esc_html_e( 'Send test metric', 'bluem' ); ?></button></p>
        </form><p class="description"><?php esc_html_e( 'Search for tag bluem_test:true; the metric name is bluem.test.metric.', 'bluem' ); ?></p></div>
        <?php
    }

    private static function available(): bool {
        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || in_array( $host, [ 'localhost', '127.0.0.1', '::1', 'wordpress' ], true );
    }
}
