<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin;

use Modestox\ConfigProcessorWp\Admin\Ui\Builder;
use Modestox\ConfigProcessorWp\Plugin;
use Modestox\ConfigProcessorWp\Registry\ConfigRegistry;

/**
 * Class AdminPages
 *
 * Adds the menu entries of all registered pages, renders them and loads the assets on those pages only.
 */
final class AdminPages
{
    /**
     * Hook suffix => page slug.
     *
     * @var array<string, string>
     */
    private array $hooks = [];

    public function __construct(
        private readonly ConfigRegistry $registry,
        private readonly string $pluginFile,
    ) {}

    /**
     * @return void
     */
    public function register(): void
    {
        foreach ($this->registry->pages() as $slug => $page) {
            $callback = function () use ($slug): void {
                $this->render($slug);
            };

            $hook = $page->parentSlug === null
                ? add_menu_page($page->pageTitle, $page->menuTitle, $page->capability, $slug, $callback, $page->icon)
                : add_submenu_page($page->parentSlug, $page->pageTitle, $page->menuTitle, $page->capability, $slug, $callback);

            if (is_string($hook) && $hook !== '') {
                $this->hooks[$hook] = $slug;
            }
        }
    }

    /**
     * @param string $hookSuffix
     * @return void
     */
    public function enqueueAssets(string $hookSuffix): void
    {
        if (!isset($this->hooks[$hookSuffix])) {
            return;
        }

        $baseUrl = plugin_dir_url($this->pluginFile);

        wp_enqueue_media();
        wp_enqueue_style('modestox-admin-settings', $baseUrl . 'assets/css/admin-settings.css', [], Plugin::VERSION);
        wp_enqueue_script('modestox-admin-fields', $baseUrl . 'assets/js/admin-fields.js', [], Plugin::VERSION, true);
        wp_enqueue_script('modestox-admin-depends', $baseUrl . 'assets/js/admin-depends.js', [], Plugin::VERSION, true);
    }

    /**
     * @param string $slug
     * @return void
     */
    private function render(string $slug): void
    {
        $page = $this->registry->page($slug);

        if ($page === null || !current_user_can($page->capability)) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'modestox-config-processor-wp'), 403);
        }

        (new Builder($page, $this->registry->types()))->render();
    }
}