<?php

namespace Bluem\Wordpress\Observability;

use Sentry\State\Scope;
use function Sentry\configureScope;

final class SentryLogger
{
    private const KEY = 'ce6a8fc06ff29a03f805eae2041fdd4e@o4506286009548800';
    private const PROJECT_ID = 4506286012891136;

    private const ENV_DEVELOPMENT = 'development';
    private const ENV_PRODUCTION = 'production';

    public function initialize(): void
    {
        $bluem_options = get_option( 'bluem_woocommerce_options' );
        $plugin_version = $bluem_options['bluem_plugin_version'] ?? '0';

        \Sentry\init([
            'dsn' => 'https://'.self::KEY.'.ingest.sentry.io/'.self::PROJECT_ID,
            'environment' => $this->getEnvironment(),
            'attach_stacktrace'=> true,
            'release' => 'bluem-woocommerce@'.$plugin_version,
        ]);


        $values = get_option( 'bluem_woocommerce_options' );
        $senderId =$values['senderID'] ?? '';

        configureScope(function (Scope $scope) use ($senderId): void {
            $scope->setContext('website', [
                'name' => get_bloginfo( 'name' ),
                'email' => get_option( "admin_email" ),
                'url'=> home_url(),
            ]);
            $scope->setTag('bluemSenderId', $senderId);
        });
    }

    private function getEnvironment(): string
    {
        if($_SERVER['SERVER_NAME'] === 'localhost') {
            return self::ENV_DEVELOPMENT;
        }

        return self::ENV_PRODUCTION;
    }
}
