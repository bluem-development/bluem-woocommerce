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
        $I->see('Payment methods');

        foreach ([
            'bluem_payments_ideal',
            'bluem_payments_paypal',
            'bluem_payments_creditcard',
            'bluem_payments_sofort',
            'bluem_payments_cartebancaire',
        ] as $gatewayId) {
            $I->seeElement('a[href*="section=' . $gatewayId . '"]');
        }
    }

    private function login(AcceptanceTester $I): void
    {
        $I->amOnPage('/wp-admin');
        $I->fillField('input[name="log"]', 'wordpress');
        $I->fillField('input[name="pwd"]', 'wordpress');
        $I->click('#wp-submit');
    }
}
