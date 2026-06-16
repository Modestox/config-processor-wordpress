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
 * Class TabNavigation
 *
 * Renders sidebar navigation mapping tabs and assigned sections.
 */
class TabNavigation
{
    /**
     * TabNavigation constructor.
     *
     * @param array<string, array<string, mixed>> $tabs
     * @param array<string, array<string, mixed>> $sections
     * @param string $activeSection
     * @param string $pageSlug
     */
    public function __construct(
            private readonly array $tabs,
            private readonly array $sections,
            private readonly string $activeSection,
            private readonly string $pageSlug,
    ) {}

    /**
     * Renders the sidebar navigation tree structure.
     *
     * @return void
     */
    public function render(): void
    {
        ?>
        <div class="mtx-sys-config-sidebar">

            <?php foreach ($this->tabs as $tabKey => $tabData):
                $assignedSections = array_filter(
                        $this->sections,
                        static fn(array $section) => ($section['tab'] ?? '') === $tabKey,
                );

                if (empty($assignedSections)) {
                    continue;
                }

                $customTabClass = (string)($tabData['class'] ?? '');
                $tabIcon = (string)($tabData['icon'] ?? 'admin-generic');

                $listClass = 'mtx-sys-config-list';
                if ($customTabClass !== '') {
                    $listClass .= ' ' . $customTabClass;
                }
                ?>

                <div class="mtx-sys-config-group-header">
                    <?php if ($tabIcon !== ''): ?>
                        <span class="dashicons dashicons-<?php echo esc_attr($tabIcon); ?> mtx-sys-config-icon"></span>
                    <?php endif; ?>
                    <?php echo esc_html($tabData['label'] ?? $tabKey); ?>
                </div>

                <ul class="<?php echo esc_attr($listClass); ?>">
                    <?php
                    foreach ($assignedSections as $sectionKey => $sectionData):
                        $isActive = ($sectionKey === $this->activeSection);
                        $targetUrl = admin_url(sprintf('admin.php?page=%s&section=%s', $this->pageSlug, $sectionKey));
                        $linkClasses = $isActive ? 'mtx-sys-config-link is-active' : 'mtx-sys-config-link';
                        ?>
                        <li class="mtx-sys-config-tab-item">
                            <a href="<?php echo esc_url($targetUrl); ?>" class="<?php echo esc_attr($linkClasses); ?>">
                                <?php echo esc_html($sectionData['label'] ?? $sectionKey); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </div>
        <?php
    }
}