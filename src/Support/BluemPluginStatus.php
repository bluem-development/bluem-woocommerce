<?php

namespace Bluem\Wordpress\Support;

final class BluemPluginStatus
{
    public function isWooCommerceActive(array $activePlugins): bool
    {
        return in_array('woocommerce/woocommerce.php', $activePlugins, true);
    }

    public function isContactForm7Active(array $activePlugins): bool
    {
        return in_array('contact-form-7/wp-contact-form-7.php', $activePlugins, true);
    }

    public function isGravityFormsActive(array $activePlugins): bool
    {
        return in_array('gravityforms', $activePlugins, true)
            || in_array('gravityforms/gravityforms.php', $activePlugins, true);
    }

    public function hasPermalinks(?string $permalinkStructure): bool
    {
        return ! empty($permalinkStructure);
    }
}
