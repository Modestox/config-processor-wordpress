<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

/**
 * Plugin Name: Modestox Config Processor Integration Wordpress
 * Description: Integrates the strict PHP 8.3 Modestox Config Processor component into WordPress.
 * Version:     1.0.0
 * Author:      Sergey Kuzmitsky
 * License:     MIT
 * Requires PHP: 8.3
 * Text Domain:  modestox-config-processor-wp
 * Domain Path:  /languages
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp;

if (!defined('ABSPATH')) {
    exit;
}

// Initialize Composer Autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

/**
 * Returns the main operational instance of the plugin adapter.
 * Replaces old-school $GLOBALS entries with a clean, type-hinted function wrapper.
 *
 * @return Plugin
 */
function modestoxConfigAdapter(): Plugin
{
    return Plugin::instance();
}

// Bootstrap the plugin adapter lifecycle on WordPress initialization
add_action('plugins_loaded', static function (): void {
    modestoxConfigAdapter()->boot();
});