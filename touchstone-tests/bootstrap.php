<?php

use WPTS\Settings;

/**
 * PHPUnit bootstrap file.
 *
 * @package Wp_Touchstone_Test_Plugin
 */

if (PHP_MAJOR_VERSION >= 8) {
    echo "The scaffolded tests cannot currently be run on PHP 8.0+. See https://github.com/wp-cli/scaffold-command/issues/285" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit(1);
}

$_tests_dir = getenv('WP_TESTS_DIR');

if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (! file_exists("{$_tests_dir}/includes/functions.php")) {
    echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit(1);
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

// echo " !!! tests dir " . $settings->consumerSettings()->plugins() . "\n\n";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin()
{
    // require dirname( dirname( __FILE__ ) ) . '/../../../wp-touchstone-test-plugin.php';

    $settings = new Settings();

    $consumer_plugins = $settings->consumerSettings()->plugins();

    if (!$consumer_plugins) {
        return;
    }

    foreach ($consumer_plugins as $plugin) {
        $file = $plugin['file'];

        if (!file_exists($file)) {
            ray("Plugin file not found", $file)->red();
            continue;
        }
        ray("Plugin file found", $file)->green();

        require $file;
    }
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
