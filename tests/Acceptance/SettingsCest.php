<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class SettingsCest
{
    /**
     * @group settings
     */
    public function settingsPersistThroughWordPressOptionsFlow(AcceptanceTester $I): void
    {
        $this->login($I);
        $I->amOnPage('/wp-admin/admin.php?page=bluem-settings');
        $I->see('Settings');
        $I->seeElement('#bluem_woocommerce_settings_senderID');

        $I->fillField('#bluem_woocommerce_settings_senderID', 'acceptance-settings-sender');
        $I->selectOption('#bluem_woocommerce_settings_environment', 'prod');
        $I->click('input[name="submit"]');
        $I->see('Settings');

        $I->amOnPage('/wp-admin/admin.php?page=bluem-settings');
        $I->seeInField('#bluem_woocommerce_settings_senderID', 'acceptance-settings-sender');
        $I->seeOptionIsSelected('#bluem_woocommerce_settings_environment', 'Production (live)');
    }

    private function login(AcceptanceTester $I): void
    {
        $I->amOnPage('/wp-admin');
        $I->fillField('input[name="log"]', 'wordpress');
        $I->fillField('input[name="pwd"]', 'wordpress');
        $I->click('#wp-submit');
    }
}
