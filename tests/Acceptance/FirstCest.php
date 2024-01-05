<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class FirstCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function frontpageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Home');
    }

    public function loginPageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/wp-admin');
        $I->see('Username or Email Address');
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
        $I->click('Log In');
        $I->see('Error: The password you entered for the username wordpress is incorrect.');

    }

    public function logout(AcceptanceTester $I)
    {
        $this->ifIAmLoggedIn($I);
        $I->click('#wp-admin-bar-my-account > a');
        $I->click('#wp-admin-bar-logout > a');
        $I->see('logged out');
    }

    private function ifIAmLoggedIn($I): void
    {
        $I->amOnPage('/wp-admin');
        $I->fillField('input[name="log"]', 'wordpress');
        $I->fillField('input[name="pwd"]', 'wordpress');
        $I->click('Log In');
    }

    public function visitBluemAdminPage(AcceptanceTester $I)
    {
        $this->ifIAmLoggedIn($I);
        $I->amOnPage('/wp-admin/admin.php?page=bluem-admin');
        $I->see('Maak betalen gemakkelijk!');
    }
}
