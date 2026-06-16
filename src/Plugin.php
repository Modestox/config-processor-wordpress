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
use Modestox\ConfigProcessorWp\Builder\ConfigMerger;
use Modestox\ConfigProcessorWp\Exception\ConfigurationCollisionException;

/**
 * Class Plugin
 *
 * Core manager responsible for bootstrapping WordPress hooks and driving the configuration lifecycle.
 */
class Plugin
{
    /**
     * Permanent single operational identifier slug for core global control panel.
     */
    private const GLOBAL_PAGE_SLUG = 'modestox_global_settings';

    /**
     * Internal storage for processed and validated configuration page tokens.
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
        // 1. Fetch simple structured arrays from modules: $configs['my_plugin'] = require ...
        $rawGlobalFilters = (array)apply_filters('modestox_register_global_config', []);

        try {
            // Delegate complex merging and path checking to the proper core builder class
            $globalSchema = ConfigMerger::mergeGlobalRegistry($rawGlobalFilters);
        } catch (ConfigurationCollisionException $e) {
            // Check if it is an administrative screen or an AJAX/REST request to prevent breaking APIs
            if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
                wp_die(
                    esc_html($e->getMessage()),
                    'Modestox Config Error',
                    ['response' => 500, 'back_link' => true]
                );
            }
            return;
        }

        // Pack validated schema array into monolithic core page payload layout structure
        $globalConfigs = [];
        if (!empty($globalSchema['tabs']) || !empty($globalSchema['sections'])) {
            $globalConfigs[self::GLOBAL_PAGE_SLUG] = [
                'menu_title' => 'Modestox Global Settings',
                'capability' => 'manage_options',
                'icon'       => 'dashicons-admin-settings',
                'schema'     => $globalSchema,
            ];
        }

        // 2. Fetch standard local flat plugin settings
        $pluginConfigs = (array)apply_filters('modestox_register_admin_plugin_config', []);

        // Cache unified pages nodes state
        $this->configPages = array_merge($globalConfigs, $pluginConfigs);

        if (is_admin() && (!empty($globalConfigs) || !empty($pluginConfigs))) {
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
        if (empty($this->configPages)) {
            return;
        }

        if (isset($this->configPages[self::GLOBAL_PAGE_SLUG])) {
            $globalPage = $this->configPages[self::GLOBAL_PAGE_SLUG];
            add_menu_page(
                (string)$globalPage['menu_title'],
                (string)$globalPage['menu_title'],
                (string)$globalPage['capability'],
                self::GLOBAL_PAGE_SLUG,
                [$this, 'renderPageContainer'],
                (string)$globalPage['icon']
            );
        }

        foreach ($this->configPages as $pageSlug => $pageData) {
            if ($pageSlug === self::GLOBAL_PAGE_SLUG) {
                continue;
            }

            $parentSlug = (string)($pageData['parent_slug'] ?? 'options-general.php');
            $menuTitle  = (string)($pageData['menu_title'] ?? 'Plugin Options');
            $capability = (string)($pageData['capability'] ?? 'manage_options');

            add_submenu_page(
                $parentSlug,
                $menuTitle,
                $menuTitle,
                $capability,
                $pageSlug,
                [$this, 'renderPageContainer']
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

                wp_enqueue_media();

                $cssUrl = plugins_url('assets/css/admin-settings.css', $basePath);
                wp_enqueue_style('modestox-admin-settings', $cssUrl, [], '1.0.0');

                $jsUrl = plugins_url('assets/js/admin-fields.js', $basePath);
                wp_enqueue_script('modestox-admin-fields', $jsUrl, [], '1.0.0', true);

                $customJsUrl = plugins_url('assets/js/admin-depends.js', $basePath);
                wp_enqueue_script('modestox-admin-depends', $customJsUrl, ['modestox-admin-fields'], '1.0.0', true);
                break;
            }
        }
    }

    /**
     * Master rendering route container.
     *
     * @return void
     */
    public function renderPageContainer(): void
    {
        $globalConfigs = [];
        if (isset($this->configPages[self::GLOBAL_PAGE_SLUG])) {
            $globalConfigs[self::GLOBAL_PAGE_SLUG] = $this->configPages[self::GLOBAL_PAGE_SLUG];
        }

        $pluginConfigs = $this->configPages;
        unset($pluginConfigs[self::GLOBAL_PAGE_SLUG]);

        (new PageRenderer($globalConfigs, $pluginConfigs))->render();
    }
}