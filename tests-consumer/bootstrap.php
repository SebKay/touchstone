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
 * Manually load plugins and theme for use in tests
 */
require_once "{$tests_dir}/includes/functions.php";

tests_add_filter('muplugins_loaded', function () use ($settings) {
    //---- Plugins
    foreach ($settings->consumerSettings()->plugins() as $plugin) {
        if (!file_exists($plugin->filePath())) {
            continue;
        }

        require $plugin->filePath();
    }

    //---- Theme
    $theme = $settings->consumerSettings()->theme();

    if ($theme) {
        if (is_dir($theme->directoryPath())) {
            $current_theme = $settings->consumerSettings()->theme()->directoryName();
            $theme_root    = dirname($theme->directoryPath());

            add_filter('theme_root', function () use ($theme_root) {
                return $theme_root;
            });

            register_theme_directory($theme_root);

            add_filter('pre_option_template', function () use ($current_theme) {
                return $current_theme;
            });

            add_filter('pre_option_stylesheet', function () use ($current_theme) {
                return $current_theme;
            });
        }
    }
});

/**
 * Start the WP testing environment
 */
require "{$tests_dir}/includes/bootstrap.php";
