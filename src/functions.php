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

if (!function_exists('modestox_fields')) {
    /**
     * Field rendering for your own forms (CRUD screens, meta boxes, custom admin pages).
     *
     *     $ui = modestox_fields();
     *     if ($ui !== null) {
     *         $field = $ui->field('text', 'title', ['label' => 'Title', 'required' => true]);
     *         $field->renderRow($note['title'] ?? null);
     *     }
     *
     * @return \Modestox\ConfigProcessorWp\Api\FormFields|null Null when the adapter is not active or 'init' has not run yet.
     */
    function modestox_fields(): ?\Modestox\ConfigProcessorWp\Api\FormFields
    {
        if (!class_exists(Plugin::class) || !Plugin::instance()->isBooted()) {
            return null;
        }

        if (!did_action('init')) {
            _doing_it_wrong('modestox_fields', 'Fields are available from the "init" action on.', Plugin::VERSION);
            return null;
        }

        return Plugin::instance()->fields();
    }
}