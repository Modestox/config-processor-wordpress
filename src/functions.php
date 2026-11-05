<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

use Modestox\ConfigProcessorWp\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('modestox_config')) {
    /**
     * Returns a typed settings value registered through the Modestox filters.
     *
     * The saved value is returned when it exists and is still valid for the field,
     * otherwise the field default from the schema.
     *
     * Paths:
     *  - settings page of a plugin ('modestox_register_admin_plugin_config'): 'group/field'
     *  - global settings page ('modestox_register_global_config'):           'section/group/field'
     *
     * @param string $plugin The 'plugin' identifier used in the registration entry.
     * @param string $path   Path of the field, see above.
     * @return mixed         Null when the field is unknown or the adapter is not active.
     */
    function modestox_config(string $plugin, string $path): mixed
    {
        if (!class_exists(Plugin::class) || !Plugin::instance()->isBooted()) {
            return null;
        }

        return Plugin::instance()->registry()->value($plugin, $path);
    }
}