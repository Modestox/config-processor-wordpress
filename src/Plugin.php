<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp;

use Modestox\ConfigProcessorWp\Admin\PageRenderer;
use Modestox\ConfigProcessorWp\Exception\ConfigurationCollisionException;

/**
 * Class Plugin
 *
 * Core manager responsible for bootstrapping WordPress hooks and driving the configuration lifecycle.
 */
class Plugin
{
    /**
     * Internal storage for processed and validated configuration pages.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configPages = [];

    /**
     * Registers core WordPress bootstrap hooks.
     *
     * @return void
     */
    public function initialize(): void
    {
        // Early initialization to ensure settings are registered for POST requests.
        add_action('init', [$this, 'bootConfiguration'], 5);

        add_action('admin_menu', [$this, 'registerAdminMenus'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminStyles']);
    }

    /**
     * Gathers declarative configuration layers and initializes the renderer.
     *
     * @return void
     */
    public function bootConfiguration(): void
    {
        /**
         * Fetch configurations from two separate sources.
         * We keep them separate to allow PageRenderer to apply different validation rules.
         */
        $globalConfigs = (array)apply_filters('modestox_register_admin_global_config', []);
        $pluginConfigs = (array)apply_filters('modestox_register_admin_plugin_config', []);

        // Save active page slugs to internal storage so scripts can be enqueued conditionally
        $this->configPages = array_merge($globalConfigs, $pluginConfigs);

        if (is_admin() && (!empty($globalConfigs) || !empty($pluginConfigs))) {
            // Pass both sets to PageRenderer to handle them accordingly
            new PageRenderer($globalConfigs, $pluginConfigs);
        }
    }

    /**
     * Maps gathered configuration layers to WordPress admin menus.
     *
     * @return void
     */
    public function registerAdminMenus(): void
    {
        // Fetch both registries
        $globalConfigs = (array)apply_filters('modestox_register_admin_global_config', []);
        $pluginConfigs = (array)apply_filters('modestox_register_admin_plugin_config', []);

        // Register Global Menus
        foreach ($globalConfigs as $pageSlug => $pageData) {
            $menuTitle = (string)($pageData['menu_title'] ?? 'Global Options');
            $capability = (string)($pageData['capability'] ?? 'manage_options');
            $menuIcon = (string)($pageData['icon'] ?? '');

            add_menu_page(
                $menuTitle,
                $menuTitle,
                $capability,
                $pageSlug,
                [$this, 'renderPageContainer'],
                $menuIcon,
            );
        }

        // Register Plugin Menus
        foreach ($pluginConfigs as $pageSlug => $pageData) {
            $parentSlug = (string)($pageData['parent_slug'] ?? 'options-general.php');
            $menuTitle = (string)($pageData['menu_title'] ?? 'Plugin Options');
            $capability = (string)($pageData['capability'] ?? 'manage_options');

            add_submenu_page(
                $parentSlug,
                $menuTitle,
                $menuTitle,
                $capability,
                $pageSlug,
                [$this, 'renderPageContainer'],
            );
        }
    }

    /**
     * Enqueues administrative styles if the current page belongs to Modestox.
     *
     * @param string $hookSuffix
     * @return void
     */
    public function enqueueAdminStyles(string $hookSuffix): void
    {
        foreach (array_keys($this->configPages) as $pageSlug) {
            if (str_contains($hookSuffix, $pageSlug)) {
                $basePath = dirname(__DIR__) . '/modestox-config-processor-wp.php';

                // Enqueue WordPress native media manager core assets
                wp_enqueue_media();

                // Enqueue Stylesheet asset framework
                $cssUrl = plugins_url('assets/css/admin-settings.css', $basePath);
                wp_enqueue_style('modestox-admin-settings', $cssUrl, [], '1.0.0');

                // Enqueue JavaScript operational engine component layout nodes
                $jsUrl = plugins_url('assets/js/admin-fields.js', $basePath);
                wp_enqueue_script('modestox-admin-fields', $jsUrl, [], '1.0.0', true);
                break;
            }
        }
    }

    /**
     * Master rendering route container.
     * Fetches registries dynamically and instantiates the renderer with required dependencies.
     *
     * @return void
     * @throws ConfigurationCollisionException
     */
    public function renderPageContainer(): void
    {
        // Fetch both registries again, as this method runs in a fresh execution context
        $globalConfigs = (array)apply_filters('modestox_register_admin_global_config', []);
        $pluginConfigs = (array)apply_filters('modestox_register_admin_plugin_config', []);

        // Pass both arguments to satisfy the PageRenderer constructor
        (new PageRenderer($globalConfigs, $pluginConfigs))->render();
    }
}