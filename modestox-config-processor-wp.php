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
 * Description: Integrates the strict PHP 8.3 Modestox Config Processor component into WordPress 7.0.
 * Version:     1.0.0
 * Author:      Sergey Kuzmitsky
 * License:     MIT
 * Requires PHP: 8.3
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp;

// Prevent direct access to the file outside the WordPress environment
if (!defined('ABSPATH')) {
    exit;
}

// Initialize the plugin's isolated Composer autoloader
$autoloader = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

/**
 * Activation hook callback.
 * Validates that the core library was successfully linked via Composer path repositories.
 */
add_action('activate_modestox-config-processor-wp/modestox-config-processor-wp.php', function (): void {
    if (!class_exists(\Modestox\ConfigProcessor\Processor::class)) {
        wp_die(
            esc_html__(
                'Modestox Config Processor core library is missing or autoloader is not initialized. Please ensure dependencies are installed.',
                'modestox-config-processor-wp',
            ),
        );
    }
});

// Bootstrap the core plugin logic
if (class_exists(Plugin::class)) {
    (new Plugin())->initialize();
}