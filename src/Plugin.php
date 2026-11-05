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

use LogicException;
use Modestox\ConfigProcessorWp\Admin\AdminPages;
use Modestox\ConfigProcessorWp\Admin\Notices;
use Modestox\ConfigProcessorWp\Admin\SettingsRegistrar;
use Modestox\ConfigProcessorWp\Registry\ConfigRegistry;

/**
 * Class Plugin
 *
 * Bootstrap of the WordPress adapter: wires the registry, settings, admin pages and notices into WordPress hooks.
 */
final class Plugin
{
    public const VERSION = '1.1.0';
    public const TEXT_DOMAIN = 'modestox-config-processor-wp';

    private static ?self $instance = null;

    private string $pluginFile = '';

    private ?ConfigRegistry $registry = null;

    private function __construct() {}

    /**
     * Returns the single instance of the adapter.
     *
     * @return self
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Registers all hooks. Safe to call more than once.
     *
     * @param string $pluginFile Absolute path of the main plugin file.
     * @return void
     */
    public function boot(string $pluginFile): void
    {
        if ($this->registry !== null) {
            return;
        }

        $this->pluginFile = $pluginFile;
        $this->registry = new ConfigRegistry();

        add_action('init', [$this, 'loadTextDomain'], 0);

        // Settings are registered in every context, so get_option() and modestox_config() know the defaults on the front end too.
        add_action('init', [new SettingsRegistrar($this->registry), 'register'], 20);

        if (is_admin()) {
            $pages = new AdminPages($this->registry, $pluginFile);
            add_action('admin_menu', [$pages, 'register'], 20);
            add_action('admin_enqueue_scripts', [$pages, 'enqueueAssets']);
            add_action('admin_notices', [new Notices($this->registry), 'render']);
        }
    }

    /**
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->registry !== null;
    }

    /**
     * @return ConfigRegistry
     */
    public function registry(): ConfigRegistry
    {
        if ($this->registry === null) {
            throw new LogicException('The Modestox Config Processor adapter has not been booted yet.');
        }

        return $this->registry;
    }

    /**
     * @return string
     */
    public function pluginFile(): string
    {
        return $this->pluginFile;
    }

    /**
     * @return void
     */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname(plugin_basename($this->pluginFile)) . '/languages');
    }
}