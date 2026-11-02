<?php

require __DIR__ . '/SettingsBootstrap.php';
require __DIR__ . '/RequestLookupDatabase.php';

// Stop after the real request-list query, before rendering unrelated WordPress UI.
final class BluemRequestListTestStop extends RuntimeException {}
function get_users() { throw new BluemRequestListTestStop(); }
function wp_unslash($value) { return stripslashes($value); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
