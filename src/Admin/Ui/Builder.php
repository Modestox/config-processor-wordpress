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

use Modestox\ConfigProcessorWp\Admin\Ui\GlobalLayout\SectionContainer;
use Modestox\ConfigProcessorWp\Admin\Ui\GlobalLayout\TabNavigation;
use Modestox\ConfigProcessorWp\Admin\Ui\PluginLayout\GroupContainer as PluginGroupContainer;
use Modestox\ConfigProcessorWp\Field\FieldTypeRegistry;
use Modestox\ConfigProcessorWp\Registry\PageDefinition;

/**
 * Class Builder
 *
 * Prints a settings page from its validated definition.
 */
final class Builder
{
    public function __construct(
        private readonly PageDefinition $page,
        private readonly FieldTypeRegistry $types,
    ) {}

    /**
     * @return void
     */
    public function render(): void
    {
        $wrapperClass = $this->page->isGlobal() ? 'mtx-global-config-container' : 'mtx-plugin-config-wrap';
        ?>
        <div class="wrap <?php echo esc_attr($wrapperClass); ?>">
            <h1><?php echo esc_html($this->page->pageTitle); ?></h1>
            <?php
            // Pages under "Settings" get the notices from WordPress core (options-head.php).
            if ($this->page->parentSlug !== 'options-general.php') {
                settings_errors();
            }

            if ($this->page->isGlobal()) {
                $this->renderGlobalLayout();
            } else {
                $this->renderPluginLayout();
            }
            ?>
        </div>
        <?php
    }

    /**
     * @return void
     */
    private function renderPluginLayout(): void
    {
        ?>
        <form method="post" action="options.php" class="mtx-config-form">
            <?php settings_fields($this->page->optionGroup()); ?>
            <?php (new PluginGroupContainer((array)($this->page->schema['groups'] ?? []), $this->types))->render(); ?>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * @return void
     */
    private function renderGlobalLayout(): void
    {
        $tabs = (array)($this->page->schema['tabs'] ?? []);
        $sections = (array)($this->page->schema['sections'] ?? []);
        $ordered = TabNavigation::orderedSections($tabs, $sections);

        if ($ordered === []) {
            echo '<p>' . esc_html__('No settings are registered yet.', 'modestox-config-processor-wp') . '</p>';
            return;
        }

        $requested = isset($_GET['section']) ? sanitize_key(wp_unslash((string)$_GET['section'])) : '';
        $activeSection = in_array($requested, $ordered, true) ? $requested : $ordered[0];
        ?>
        <form method="post" action="options.php" class="mtx-config-form">
            <?php settings_fields($this->page->optionGroup($activeSection)); ?>
            <div class="mtx-split-layout-wrapper">
                <?php (new TabNavigation($tabs, $sections, $activeSection, $this->page->slug))->render(); ?>
                <div class="mtx-sys-config-content">
                    <?php (new SectionContainer($sections[$activeSection], $this->types))->render(); ?>
                </div>
            </div>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}