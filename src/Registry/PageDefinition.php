<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Registry;

/**
 * Class PageDefinition
 *
 * One admin settings page with its validated schema. Every storable field of the schema
 * carries its final option name under the '_option_name' key.
 */
final class PageDefinition
{
    public const LAYOUT_GLOBAL = 'global';
    public const LAYOUT_PLUGIN = 'plugin';

    /**
     * @param string $slug Admin page slug ('page' query argument).
     * @param string $layout One of the LAYOUT_* constants.
     * @param string|null $plugin Owning plugin identifier, null for the shared global page.
     * @param string $menuTitle Menu label.
     * @param string $pageTitle Page heading and document title.
     * @param string|null $parentSlug Parent menu slug, null for a top-level menu page.
     * @param string $capability Capability required to view and save the page.
     * @param string $icon Menu icon of a top-level page.
     * @param array<string, mixed> $schema Validated schema.
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $layout,
        public readonly ?string $plugin,
        public readonly string $menuTitle,
        public readonly string $pageTitle,
        public readonly ?string $parentSlug,
        public readonly string $capability,
        public readonly string $icon,
        public readonly array $schema,
    ) {}

    /**
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->layout === self::LAYOUT_GLOBAL;
    }

    /**
     * Settings API option group. The global page saves each section separately.
     *
     * @param string $section Section identifier, used by the global page only.
     * @return string
     */
    public function optionGroup(string $section = ''): string
    {
        return $this->isGlobal() ? self::globalOptionGroup($section) : self::pluginOptionGroup($this->slug);
    }

    /**
     * @param string $section
     * @return string
     */
    public static function globalOptionGroup(string $section): string
    {
        return 'mtx_global_' . $section;
    }

    /**
     * @param string $pageSlug
     * @return string
     */
    public static function pluginOptionGroup(string $pageSlug): string
    {
        return 'mtx_page_' . $pageSlug;
    }

    /**
     * @return list<string>
     */
    public function optionGroups(): array
    {
        if (!$this->isGlobal()) {
            return [$this->optionGroup()];
        }

        return array_map(fn(string|int $section): string => $this->optionGroup((string)$section), array_keys($this->schema['sections'] ?? []));
    }
}