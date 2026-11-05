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
 * Sidebar of the global settings page: tabs as headers, their sections as links.
 */
final class TabNavigation
{
    /**
     * @param array<string, array<string, mixed>> $tabs          Validated tabs (sorted).
     * @param array<string, array<string, mixed>> $sections      Validated sections (sorted).
     * @param string                              $activeSection
     * @param string                              $pageSlug
     */
    public function __construct(
        private readonly array $tabs,
        private readonly array $sections,
        private readonly string $activeSection,
        private readonly string $pageSlug,
    ) {}

    /**
     * Section identifiers in the order they appear in the sidebar.
     *
     * @param array<string, array<string, mixed>> $tabs
     * @param array<string, array<string, mixed>> $sections
     * @return list<string>
     */
    public static function orderedSections(array $tabs, array $sections): array
    {
        $ordered = [];
        foreach (array_keys($tabs) as $tabKey) {
            foreach ($sections as $sectionKey => $section) {
                if ($section['tab'] === $tabKey) {
                    $ordered[] = (string)$sectionKey;
                }
            }
        }

        return $ordered;
    }

    /**
     * Accepts 'dashicons-admin-generic' as well as 'admin-generic'.
     *
     * @param string $icon
     * @return string
     */
    public static function iconClass(string $icon): string
    {
        if ($icon === '') {
            return '';
        }

        return str_starts_with($icon, 'dashicons-') ? $icon : 'dashicons-' . $icon;
    }

    /**
     * @return void
     */
    public function render(): void
    {
        ?>
        <nav class="mtx-sys-config-sidebar">
            <?php foreach ($this->tabs as $tabKey => $tab):
                $sections = array_filter($this->sections, static fn(array $section): bool => $section['tab'] === $tabKey);
                if ($sections === []) {
                    continue;
                }
                $icon = self::iconClass((string)($tab['icon'] ?? ''));
                ?>
                <div class="mtx-sys-config-group-header">
                    <?php if ($icon !== ''): ?>
                        <span class="dashicons <?php echo esc_attr($icon); ?> mtx-sys-config-icon"></span>
                    <?php endif; ?>
                    <?php echo esc_html((string)$tab['label']); ?>
                </div>
                <ul class="<?php echo esc_attr(trim('mtx-sys-config-list ' . (string)($tab['class'] ?? ''))); ?>">
                    <?php foreach ($sections as $sectionKey => $section):
                        $isActive = (string)$sectionKey === $this->activeSection;
                        $url = add_query_arg(['page' => $this->pageSlug, 'section' => (string)$sectionKey], admin_url('admin.php'));
                        ?>
                        <li class="mtx-sys-config-tab-item">
                            <a href="<?php echo esc_url($url); ?>"
                               class="mtx-sys-config-link<?php echo $isActive ? ' is-active' : ''; ?>"
                               <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                                <?php echo esc_html((string)$section['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>
        <?php
    }
}