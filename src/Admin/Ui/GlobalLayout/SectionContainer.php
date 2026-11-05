<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui\GlobalLayout;

use Modestox\ConfigProcessorWp\Field\FieldTypeRegistry;

/**
 * Class SectionContainer
 *
 * Title and groups of the active section of the global settings page.
 */
final class SectionContainer
{
    /**
     * @param array<string, mixed> $section Validated section.
     * @param FieldTypeRegistry    $types
     */
    public function __construct(
        private readonly array $section,
        private readonly FieldTypeRegistry $types,
    ) {}

    /**
     * @return void
     */
    public function render(): void
    {
        $icon = TabNavigation::iconClass((string)($this->section['icon'] ?? ''));
        $groups = (array)($this->section['groups'] ?? []);
        ?>
        <div class="<?php echo esc_attr(trim('mtx-sys-config-section-wrapper ' . (string)($this->section['class'] ?? ''))); ?>">
            <h2 class="mtx-sys-config-section-title">
                <?php if ($icon !== ''): ?>
                    <span class="dashicons <?php echo esc_attr($icon); ?> mtx-sys-config-icon"></span>
                <?php endif; ?>
                <?php echo esc_html((string)$this->section['label']); ?>
            </h2>
            <?php if ($groups === []): ?>
                <p><?php echo esc_html__('This section has no settings yet.', 'modestox-config-processor-wp'); ?></p>
            <?php else: ?>
                <?php (new GroupContainer($groups, $this->types))->render(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
}