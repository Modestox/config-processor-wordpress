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

/**
 * Class SectionContainer
 *
 * Renders major page sections and coordinates their group child layouts.
 */
class SectionContainer
{
    /**
     * SectionContainer constructor.
     *
     * @param array<string, array<string, mixed>> $sections
     * @param string $activeSection
     * @param string $basePrefix
     */
    public function __construct(
            private readonly array $sections,
            private readonly string $activeSection,
            private readonly string $basePrefix,
    ) {}

    /**
     * Renders the active configuration section with its underlying group blocks.
     *
     * @return void
     */
    public function render(): void
    {
        if (!isset($this->sections[$this->activeSection])) {
            return;
        }

        $sectionData = (array)$this->sections[$this->activeSection];
        $groups = (array)($sectionData['groups'] ?? []);

        $customSectionClass = (string)($sectionData['class'] ?? '');
        $sectionIcon = (string)($sectionData['icon'] ?? '');

        $wrapperClass = 'mtx-sys-config-section-wrapper';
        if ($customSectionClass !== '') {
            $wrapperClass .= ' ' . $customSectionClass;
        }

        // Build hierarchical layout namespace for input field persistence mapping
        $namespace = $this->basePrefix . '_' . $this->activeSection;
        ?>
        <div class="<?php echo esc_attr($wrapperClass); ?>">
            <h2 class="mtx-sys-config-section-title">
                <?php if ($sectionIcon !== ''): ?>
                    <span class="dashicons <?php echo esc_attr($sectionIcon); ?> mtx-sys-config-icon"></span>
                <?php endif; ?>
                <?php echo esc_html($sectionData['label'] ?? $this->activeSection); ?>
            </h2>

            <?php if (!empty($groups)): ?>
                <?php (new GroupContainer($groups, $namespace))->render(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
}