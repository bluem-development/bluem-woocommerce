<?php

if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress was not loaded.\n");
    exit(1);
}

$plugin_file = WP_PLUGIN_DIR . '/bluem/bluem.php';
$language_directory = dirname(plugin_basename($plugin_file)) . '/languages';
$expected_translations = [
    'nl_NL' => 'Verzoek aangemaakt',
    'en_US' => 'Request created',
];

foreach ($expected_translations as $locale => $expected_translation) {
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

    restore_current_locale();
}

echo "Bluem NL/EN translations loaded successfully.\n";
