<?php

if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress was not loaded.\n");
    exit(1);
}

$plugin_file = WP_PLUGIN_DIR . '/bluem/bluem.php';
$language_directory = dirname(plugin_basename($plugin_file)) . '/languages';
// The translation fixture leaves the mandate module disabled.
require_once dirname($plugin_file) . '/bluem-mandates-shortcode.php';

$expected_translations = [
    'nl_NL' => ['Verzoek aangemaakt', 'Je moet nog een incassomachtiging afgeven.', 'Machtiging afgeven'],
    'en_US' => ['Request created', 'You still need to issue a direct debit mandate.', 'Start mandate process'],
];

foreach ($expected_translations as $locale => [$expected_translation, $expected_notice, $expected_button]) {
    $mo_file = WP_PLUGIN_DIR . '/bluem/languages/bluem-' . $locale . '.mo';
    if (!is_readable($mo_file)) {
        fwrite(STDERR, sprintf("Missing readable translation file: %s\n", $mo_file));
        exit(1);
    }

    switch_to_locale($locale);
    if (determine_locale() !== $locale) {
        fwrite(STDERR, sprintf("WordPress could not switch to locale %s.\n", $locale));
        exit(1);
    }
    unload_textdomain('bluem');
    $loaded = load_plugin_textdomain('bluem', false, $language_directory);
    if (!$loaded) {
        fwrite(STDERR, sprintf("The Bluem textdomain could not be registered for %s.\n", $locale));
        exit(1);
    }

    $translation = __('Request created', 'bluem');
    if ($locale === 'nl_NL' && !is_textdomain_loaded('bluem')) {
        fwrite(STDERR, sprintf("The Bluem textdomain was not loaded for %s.\n", $locale));
        exit(1);
    }

    if ($translation !== $expected_translation) {
        fwrite(
            STDERR,
            sprintf(
                "Unexpected %s translation: expected \"%s\", got \"%s\".\n",
                $locale,
                $expected_translation,
                $translation
            )
        );
        exit(1);
    }

    $form = do_shortcode('[bluem_machtigingsformulier]');
    $document = \Dom\HTMLDocument::createFromString($form, LIBXML_NOERROR, 'UTF-8');
    $button = $document->querySelector('form.bluem-mandate-form input[type="submit"].bluem-woocommerce-button-mandates');
    if (!str_contains($form, '<p>' . $expected_notice . '</p>')
        || $button?->getAttribute('value') !== $expected_button
    ) {
        fwrite(STDERR, sprintf("Unexpected mandate shortcode output for %s.\n", $locale));
        exit(1);
    }

    restore_current_locale();
}

$form = do_shortcode(<<<'SHORTCODE'
[bluem_machtigingsformulier notice="<strong>Review</strong> & approve." button_label='Say "yes" & continue']
SHORTCODE);
$document = \Dom\HTMLDocument::createFromString($form, LIBXML_NOERROR, 'UTF-8');
$button = $document->querySelector('input[type="submit"].bluem-woocommerce-button-mandates');
if (!str_contains($form, '<p>&lt;strong&gt;Review&lt;/strong&gt; &amp; approve.</p>')
    || $button?->getAttribute('value') !== 'Say "yes" & continue'
) {
    fwrite(STDERR, "Unexpected mandate shortcode output for custom labels.\n");
    exit(1);
}

echo "Bluem NL/EN translations and mandate shortcode labels rendered successfully.\n";
