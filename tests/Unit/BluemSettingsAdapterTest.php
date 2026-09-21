<?php

namespace Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemSettingsAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/SettingsBootstrap.php';
    }

    public function testCoreSchemaMatchesThePreExtractionContract(): void
    {
        // Captured from the procedural implementation on master before extraction.
        $expected = json_decode(file_get_contents(dirname(__DIR__) . '/Fixtures/core-options.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($expected, \bluem_woocommerce_get_core_options());
        self::assertSame($expected['environment'], \bluem_woocommerce_get_option('environment'));
        self::assertFalse(\bluem_woocommerce_get_option('unknown'));
    }

    public function testLabelsAndNotificationRecipientAreReadOnEachCall(): void
    {
        \bluem_woocommerce_get_core_options();
        $GLOBALS['bluem_settings_test_translation_prefix'] = 'second locale: ';
        $GLOBALS['bluem_settings_test_options']['admin_email'] = 'new+<owner>@example.invalid';

        $options = \bluem_woocommerce_get_core_options();
        self::assertSame('second locale: Choose the active mode', $options['environment']['name']);
        self::assertSame(
            'second locale: Send a notification for each transaction to new+&lt;owner&gt;@example.invalid',
            $options['transaction_notification_email']['options'][1]
        );
    }

    public function testConfigurationIncludesCoreAndIntegrationsWithOptionalModulesAbsent(): void
    {
        self::assertFalse(function_exists('bluem_woocommerce_get_idin_options'));
        self::assertFalse(function_exists('bluem_woocommerce_get_mandates_options'));
        self::assertFalse(function_exists('bluem_woocommerce_get_payments_options'));
        $config = \bluem_woocommerce_get_config();
        self::assertInstanceOf(\stdClass::class, $config);
        self::assertSame('test', $config->environment);
        self::assertSame('', $config->senderID);
        self::assertSame('N', $config->gformActive);
        self::assertFalse(property_exists($config, 'brandID'));
    }

    public function testConfigurationUsesCurrentSavedValuesAndFeatureMergeOrder(): void
    {
        // Declare providers after bootstrap so load-time module behavior is unchanged.
        require dirname(__DIR__) . '/Support/SettingsFeatureProviders.php';
        $GLOBALS['bluem_settings_test_options']['bluem_woocommerce_options'] = [
            'environment' => 'prod', 'senderID' => 'S12345',
            'shared' => null, 'gformActive' => '1', 'undeclared' => 'ignored',
        ];
        $config = \bluem_woocommerce_get_config();
        self::assertSame('prod', $config->environment);
        self::assertSame('S12345', $config->senderID);
        self::assertSame('payment', $config->shared);
        self::assertSame('1', $config->gformActive);
        // The integration schema is last, overriding the test payment definition.
        self::assertSame('N', $config->wpcf7Active);
        self::assertFalse(property_exists($config, 'undeclared'));

        $GLOBALS['bluem_settings_test_options']['bluem_woocommerce_options']['senderID'] = 'S67890';
        self::assertSame('S67890', \bluem_woocommerce_get_config()->senderID);
    }
}
