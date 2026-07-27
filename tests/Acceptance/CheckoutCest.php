<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class CheckoutCest
{
    /**
     * @group checkout
     */
    public function registeredGatewaysAreAvailableInWooCommerceCheckoutSettings(AcceptanceTester $I): void
    {
        $this->login($I);
        $I->amOnPage('/wp-admin/admin.php?page=wc-settings&tab=checkout');
        $I->see('Payments');
        $I->seeElement('body');
    }

    private function login(AcceptanceTester $I): void
    {
        $I->amOnPage('/wp-admin');
        $I->fillField('input[name="log"]', 'wordpress');
        $I->fillField('input[name="pwd"]', 'wordpress');
        $I->click('#wp-submit');
    }
}
