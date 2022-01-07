<?php

use WPTS\Settings;

$settings = new Settings();

/**
 * Check consumer isn't running PHP 8.0+
 */
if (PHP_MAJOR_VERSION >= 8) {
    echo "The scaffolded tests cannot currently be run on PHP 8.0+. See https://github.com/wp-cli/scaffold-command/issues/285" . PHP_EOL;
    exit(1);
}

/**
 * Set up
 */
$tests_dir = $settings->wpTestFilesDirectory();

if (!file_exists("{$tests_dir}/includes/functions.php")) {
    echo "Could not find {$tests_dir}/includes/functions.php,?" . PHP_EOL;
    exit(1);
}

/**
 * Manually load plugins for use in tests
 */
require_once "{$tests_dir}/includes/functions.php";

tests_add_filter('muplugins_loaded', function () use ($settings) {
    foreach ($settings->consumerSettings()->plugins() as $plugin) {
        if (!file_exists($plugin->filePath())) {
            continue;
        }

        require $plugin->filePath();
    }
});

/**
 * Start the WP testing environment
 */
require "{$tests_dir}/includes/bootstrap.php";
