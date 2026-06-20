<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui;

use Modestox\ConfigProcessorWp\Admin\Ui\PluginLayout\GroupContainer as PluginGroupContainer;
use Modestox\ConfigProcessorWp\Admin\Ui\GlobalLayout\TabNavigation;
use Modestox\ConfigProcessorWp\Admin\Ui\GlobalLayout\SectionContainer;

/**
 * Class Builder
 *
 * Coordinates rendering of standalone plugin layouts or multi-section global configurations.
 */
class Builder
{
    /**
     * Builder constructor.
     *
     * @param array<string, mixed> $schema
     * @param array<int, array<string, mixed>> $pageParams
     * @param string $pageSlug
     */
    public function __construct(
            private array $schema,
            private readonly array $pageParams,
            private readonly string $pageSlug = ''
    ) {}

    /**
     * Dispatches the layout building process based on layout schema.
     *
     * @return void
     */
    public function render(): void
    {
        if (isset($this->schema['sections'])) {
            $this->renderGlobalLayout();
        } else {
            $this->renderPluginLayout();
        }
    }

    /**
     * Renders standalone plugin settings form.
     *
     * @return void
     */
    private function renderPluginLayout(): void
    {
        $pageData = (array)($this->pageParams[0] ?? []);
        $prefix = (string)($pageData['option_prefix'] ?? '');

        ?>
        <div class="wrap mtx-plugin-config-wrap">
            <form method="post" action="options.php" novalidate="novalidate">
                <?php
                settings_fields('mtx_group_' . $this->pageSlug);
                ?>

                <?php (new PluginGroupContainer($this->schema['groups'], $prefix))->render(); ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renders split-layout global system settings screen.
     *
     * @return void
     */
    private function renderGlobalLayout(): void
    {
        $pageData = (array)($this->pageParams[0] ?? []);
        $basePrefix = 'mtx';

        $sections = (array)($this->schema['sections'] ?? []);
        if (empty($sections)) {
            return;
        }

        // Safely extract the active section from query parameters
        $firstSectionKey = (string)key($sections);
        $activeSection = sanitize_key((string)($_GET['section'] ?? $firstSectionKey));
        if (!isset($sections[$activeSection])) {
            $activeSection = $firstSectionKey;
        }

        $tabs = (array)($this->schema['tabs'] ?? []);
        $menuTitle = (string)($pageData['menu_title'] ?? esc_html__('Global Settings'));
        ?>
        <div class="wrap mtx-global-config-container">
            <h2><?php echo esc_html($menuTitle); ?></h2>

            <form method="post" action="options.php" novalidate="novalidate">
                <?php
                settings_fields('mtx_group_' . $this->pageSlug . '_' . $activeSection);
                ?>

                <div class="mtx-split-layout-wrapper" style="display: flex; gap: 20px; margin-top: 20px; align-items: flex-start;">

                    <?php (new TabNavigation($tabs, $sections, $activeSection, $this->pageSlug))->render(); ?>

                    <div class="mtx-sys-config-content" style="flex-grow: 1; background: #fff; border: 1px solid #ccd0d4; padding: 20px 25px; border-radius: 4px;">
                        <?php (new SectionContainer($sections, $activeSection, $basePrefix))->render(); ?>
                    </div>

                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}