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
 * Main bootstrap class for the WordPress adapter plugin.
 */
final class Plugin
{
    /**
     * Permanent single operational identifier slug for core global control panel.
     */
    private const GLOBAL_PAGE_SLUG = 'modestox_global_settings';

    /**
     * The single active instance of the adapter plugin.
     */
    private static ?self $instance = null;

    /**
     * Internal storage for processed and validated configuration page tokens.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $configPages = [];

    /**
     * Strict private constructor to enforce singleton isolation constraints.
     */
    private function __construct() {}

    /**
     * Returns the master single instance of the plugin adapter.
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Orchestrates the boot sequence and hooks of the adapter.
     *
     * @return void
     */
    public function boot(): void
    {
        // Load operational translations textdomain files smoothly
        load_plugin_textdomain(
            'modestox-config-processor-wp',
            false,
            dirname(plugin_basename(__DIR__)) . '/languages',
        );

        // Standard WordPress context routing optimization rule
        if (is_admin()) {
            $this->initializeAdminHooks();
        }
    }

    /**
     * Registers core administrative hooks into the active request lifecycle stream.
     *
     * @return void
     */
    private function initializeAdminHooks(): void
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
        // 1. Fetch simple structured arrays pushed by modules: $configs[] = ['plugin' => 'my_plugin', 'schema' => require ...]
        $rawGlobalFilters = (array)apply_filters('modestox_register_global_config', []);

        try {
            // Delegate complex merging and path checking to the proper core builder class
            $globalSchema = ConfigMerger::mergeGlobalRegistry($rawGlobalFilters);
        } catch (ConfigurationCollisionException $e) {
            if (is_admin() && (!defined('DOING_AJAX') || \DOING_AJAX === false)) {
                wp_die(
                    esc_html($e->getMessage()),
                    'Modestox Config Error',
                    ['response' => 500, 'back_link' => true],
                );
            }
            return;
        }

        // Pack validated schema array into monolithic core page payload layout structure
        $globalConfigs = [];
        if (!empty($globalSchema['tabs']) || !empty($globalSchema['sections'])) {
            $globalConfigs[self::GLOBAL_PAGE_SLUG] = [
                'menu_title' => esc_html__('Global Settings', 'modestox-config-processor-wp'),
                'capability' => 'manage_options',
                'icon'       => 'dashicons-admin-settings',
                'schema'     => $globalSchema,
            ];
        }

        // 2. Fetch standard local flat plugin settings via secure flat push format
        $rawPluginFilters = (array)apply_filters('modestox_register_admin_plugin_config', []);
        $pluginConfigs = [];
        $registeredPagesMap = [];
        $registeredPluginSlugsMap = [];

        foreach ($rawPluginFilters as $pluginPayload) {
            if (!is_array($pluginPayload)) {
                continue;
            }

            $pluginSlug = (string)($pluginPayload['plugin'] ?? '');
            $pageSlug = (string)($pluginPayload['page_slug'] ?? '');
            $schema = $pluginPayload['schema'] ?? null;

            if ($pluginSlug === '' || $pageSlug === '' || !is_array($schema)) {
                continue;
            }

            // Guard A: Protect against duplicate plugin registration in the admin pool
            if (isset($registeredPluginSlugsMap[$pluginSlug])) {
                if (is_admin() && (!defined('DOING_AJAX') || \DOING_AJAX === false)) {
                    wp_die(
                        sprintf("Security Alert! Plugin '%s' is registered multiple times in the admin config pool. Blocked.", esc_html($pluginSlug)),
                        'Modestox Security Error',
                        ['response' => 500, 'back_link' => true],
                    );
                }
                return;
            }

            // Guard B: Prevent page slug hijacking or collisions between different plugins
            if ($pageSlug === self::GLOBAL_PAGE_SLUG || isset($registeredPagesMap[$pageSlug])) {
                if (is_admin() && (!defined('DOING_AJAX') || \DOING_AJAX === false)) {
                    wp_die(
                        sprintf("Configuration Error! Administrative page slug '%s' is already occupied by another module.", esc_html($pageSlug)),
                        'Modestox Duplicate Page Error',
                        ['response' => 500, 'back_link' => true],
                    );
                }
                return;
            }

            $registeredPluginSlugsMap[$pluginSlug] = true;
            $registeredPagesMap[$pageSlug] = true;

            $pluginConfigs[$pageSlug] = [
                'parent_slug'   => (string)($pluginPayload['parent_slug'] ?? 'options-general.php'),
                'menu_title'    => (string)($pluginPayload['menu_title'] ?? esc_html__('Plugin Options', 'modestox-config-processor-wp')),
                'capability'    => (string)($pluginPayload['capability'] ?? 'manage_options'),
                'option_prefix' => (string)($pluginPayload['option_prefix'] ?? $pluginSlug),
                'schema'        => $schema,
            ];
        }

        // Cache unified pages nodes state securely
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
                (string)$globalPage['icon'],
            );
        }

        foreach ($this->configPages as $pageSlug => $pageData) {
            if ($pageSlug === self::GLOBAL_PAGE_SLUG) {
                continue;
            }

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

                wp_enqueue_media();

                $cssUrl = plugins_url('assets/css/admin-settings.css', $basePath);
                wp_enqueue_style('modestox-admin-settings', $cssUrl, [], '1.0.0');

                $jsUrl = plugins_url('assets/js/admin-fields.js', $basePath);
                wp_enqueue_script('modestox-admin-fields', $jsUrl, [], '1.0.0', true);

                wp_localize_script('modestox-admin-fields', 'mtxConfigData', [
                    'siteName' => get_bloginfo('name'),
                ]);

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