<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

/**
 * Plugin Name:       Modestox Config Processor Integration Wordpress
 * Description:       Builds WordPress admin settings pages from declarative arrays validated by Modestox Config Processor.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.3
 * Author:            Sergey Kuzmitsky
 * License:           MIT
 * Text Domain:       modestox-config-processor-wp
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp;

if (!defined('ABSPATH')) {
    exit;
}

// The bundled autoloader is optional: the plugin may also be installed through the site's root Composer project.
$autoloader = __DIR__ . '/vendor/autoload.php';
if (is_readable($autoloader)) {
    require_once $autoloader;
}

require_once __DIR__ . '/src/functions.php';

/**
 * Returns the main instance of the adapter.
 *
 * @return Plugin
 */
function modestoxConfigAdapter(): Plugin
{
    return Plugin::instance();
}

add_action('plugins_loaded', static function (): void {
    if (!class_exists(Plugin::class) || !class_exists(\Modestox\ConfigProcessor\Processor::class)) {
        add_action('admin_notices', static function (): void {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html(
                    'Modestox Config Processor Integration: dependencies are missing. '
                    . 'Run "composer install" in the plugin directory or install modestox/config-processor in the site Composer project.',
                ),
            );
        });
        return;
    }

    Plugin::instance()->boot(__FILE__);
});