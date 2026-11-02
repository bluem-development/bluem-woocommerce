<?php

namespace Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemIdentitySettingsAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/SettingsBootstrap.php';
        require dirname(__DIR__, 2) . '/bluem-idin.php';
        $GLOBALS['current_user'] = (object) ['display_name' => 'First customer'];
    }

    public function testReturnsDefinitionsRatherThanSavedValues(): void
    {
        $GLOBALS['bluem_settings_test_options']['bluem_woocommerce_options'] = ['IDINBrandID' => 'saved-brand'];
        foreach (\bluem_woocommerce_get_idin_options() as $key => $definition) {
            self::assertSame($definition, \bluem_woocommerce_get_idin_option($key));
        }
        self::assertSame('', \bluem_woocommerce_get_idin_option('IDINBrandID')['default']);
        self::assertFalse(\bluem_woocommerce_get_idin_option('missing'));
    }

    public function testDefinitionsAreRebuiltWithCurrentLocaleAndUser(): void
    {
        $before = \bluem_woocommerce_get_idin_option('IDINDescription');
        $GLOBALS['bluem_settings_test_translation_prefix'] = 'new locale: ';
        $GLOBALS['current_user']->display_name = 'Second customer';
        $GLOBALS['bluem_settings_test_options']['bluem_woocommerce_options'] = [
            'IDINDescription' => 'Request for {gebruikersnaam}',
        ];
        $after = \bluem_woocommerce_get_idin_option('IDINDescription');
        self::assertNotSame($before, $after);
        self::assertSame(\bluem_woocommerce_get_idin_options()['IDINDescription'], $after);
        self::assertStringContainsString('Second customer', json_encode($after));
        self::assertStringContainsString('new locale: ', json_encode($after));
    }
}
