<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class FirstCest
{
    public function _before(AcceptanceTester $I) {}

    /**
     * @group smoke
     */
    public function frontpageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('wordpress');
        $I->seeElement('body');
    }

    /**
     * @group smoke
     */
    public function loginPageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/wp-admin');
        $I->seeElement('input[name="log"]');
        $I->seeElement('input[name="pwd"]');
        $I->seeElement('#wp-submit');
    }

    public function loginWithValidCredentials(AcceptanceTester $I)
    {
        $this->ifIAmLoggedIn($I);
        $I->see('Dashboard');
    }

    public function loginWithInvalidCredentials(AcceptanceTester $I)
    {
        $I->amOnPage('/wp-login.php');
        $I->fillField('input[name="log"]', 'wordpress');
        $I->fillField('input[name="pwd"]', 'notwordpress');
        $I->click('#wp-submit');
        $I->seeElement('#login_error');
    }

    public function logout(AcceptanceTester $I)
    {
        $this->ifIAmLoggedIn($I);
        $I->click('#wp-admin-bar-my-account > a');
        $I->click('#wp-admin-bar-logout > a');
        $I->see('logged out');
    }

    private function ifIAmLoggedIn(AcceptanceTester $I): void
    {
        $I->amOnPage('/wp-admin');
        $I->fillField('input[name="log"]', 'wordpress');
        $I->fillField('input[name="pwd"]', 'wordpress');
        $I->click('#wp-submit');
    }

    /**
     * @group smoke
     */
    public function visitBluemAdminPage(AcceptanceTester $I)
    {
        $this->ifIAmLoggedIn($I);
        $I->amOnPage('/wp-admin/admin.php?page=bluem-admin');
        $I->see('Make payments easy!');
        $I->see("With the Bluem WordPress plugin, you can easily integrate online payments, identity checks and age verifications on your website.");
    }
}
