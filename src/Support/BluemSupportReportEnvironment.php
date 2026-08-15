<?php

namespace Bluem\Wordpress\Support;

use Closure;

final class BluemSupportReportEnvironment
{
    public function __construct(
        private readonly Closure $pluginVersion,
        private readonly Closure $bluemPhpVersion,
        private readonly Closure $phpVersion,
        private readonly Closure $wordpressVersion,
        private readonly Closure $woocommerceVersion,
        private readonly Closure $siteUrl
    ) {
    }

    public function collect(): array
    {
        return [
            'plugin_version' => ($this->pluginVersion)(),
            'bluem_php_version' => ($this->bluemPhpVersion)(),
            'php_version' => ($this->phpVersion)(),
            'wordpress_version' => ($this->wordpressVersion)(),
            'woocommerce_version' => ($this->woocommerceVersion)(),
            'site_url' => ($this->siteUrl)(),
        ];
    }
}
