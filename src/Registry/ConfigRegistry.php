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

use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Validator\Identifier;
use Modestox\ConfigProcessor\Validator\Keys;
use Modestox\ConfigProcessorWp\Admin\OptionNameBuilder;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\AbstractField;
use Modestox\ConfigProcessorWp\Builder\ConfigMerger;
use Modestox\ConfigProcessorWp\Exception\OptionCollisionException;
use Modestox\ConfigProcessorWp\Field\FieldTypeRegistry;
use Modestox\ConfigProcessorWp\Plugin;

/**
 * Class ConfigRegistry
 *
 * Collects the entries of both registration filters once per request, validates them with
 * Modestox Config Processor and keeps the clean result: admin pages, options and the lookup index.
 *
 * A broken entry never stops the others: it is skipped and reported through errors().
 *
 * Global settings ('modestox_register_global_config'):
 *
 *     $configs[] = ['plugin' => 'my_plugin', 'schema' => [ 'tabs' => [...], 'sections' => [...] ]];
 *
 * Settings page of a plugin ('modestox_register_admin_plugin_config'):
 *
 *     $pages[] = [
 *         'plugin'        => 'my_plugin',            // required
 *         'page_slug'     => 'my_plugin_settings',   // required
 *         'schema'        => [ 'groups' => [...] ],  // required
 *         'parent_slug'   => 'options-general.php',
 *         'menu_title'    => 'My Plugin',
 *         'page_title'    => 'My Plugin Settings',
 *         'capability'    => 'manage_options',
 *         'option_prefix' => 'my_plugin',
 *     ];
 */
final class ConfigRegistry
{
    public const GLOBAL_FILTER = 'modestox_register_global_config';
    public const PLUGIN_FILTER = 'modestox_register_admin_plugin_config';
    public const FIELD_TYPES_FILTER = 'modestox_register_field_types';

    public const GLOBAL_PAGE_SLUG = 'modestox_global_settings';
    public const GLOBAL_OPTION_PREFIX = 'mtx';

    private const GLOBAL_ENTRY_KEYS = ['plugin', 'schema'];
    private const PLUGIN_ENTRY_KEYS = [
        'plugin',
        'page_slug',
        'schema',
        'parent_slug',
        'menu_title',
        'page_title',
        'capability',
        'option_prefix',
    ];
    private const PAGE_SLUG_PATTERN = '/^[a-z0-9_-]+$/D';

    private bool $built = false;

    private ?FieldTypeRegistry $types = null;

    /**
     * @var array<string, PageDefinition>
     */
    private array $pages = [];

    /**
     * @var array<string, OptionDefinition>
     */
    private array $options = [];

    /**
     * Plugin => path => option name.
     *
     * @var array<string, array<string, string>>
     */
    private array $index = [];

    /**
     * @var list<string>
     */
    private array $errors = [];

    /**
     * @return array<string, PageDefinition>
     */
    public function pages(): array
    {
        $this->build();
        return $this->pages;
    }

    /**
     * @param string $slug
     * @return PageDefinition|null
     */
    public function page(string $slug): ?PageDefinition
    {
        $this->build();
        return $this->pages[$slug] ?? null;
    }

    /**
     * @return array<string, OptionDefinition>
     */
    public function options(): array
    {
        $this->build();
        return $this->options;
    }

    /**
     * @return FieldTypeRegistry
     */
    public function types(): FieldTypeRegistry
    {
        $this->build();
        /** @var FieldTypeRegistry */
        return $this->types;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        $this->build();
        return $this->errors;
    }

    /**
     * @param string $plugin
     * @param string $path 'group/field' or 'section/group/field'.
     * @return OptionDefinition|null
     */
    public function option(string $plugin, string $path): ?OptionDefinition
    {
        $this->build();
        $name = $this->index[$plugin][trim($path, '/')] ?? null;

        return $name === null ? null : $this->options[$name];
    }

    /**
     * Typed value of a field: the saved value, or the default when nothing valid is saved.
     *
     * @param string $plugin
     * @param string $path
     * @return mixed
     */
    public function value(string $plugin, string $path): mixed
    {
        if (!did_action('init')) {
            _doing_it_wrong(
                'modestox_config',
                'Settings registered through the Modestox filters are available from the "init" action on.',
                Plugin::VERSION,
            );
            return null;
        }

        $option = $this->option($plugin, $path);
        if ($option === null) {
            return null;
        }

        $renderer = $this->types()->renderer((string)$option->field['type']);

        return $renderer->cast(get_option($option->name, $renderer->defaultValue($option->field)), $option->field);
    }

    /**
     * @return void
     */
    private function build(): void
    {
        if ($this->built) {
            return;
        }
        // Set first: a filter callback that reads settings must not start a second build.
        $this->built = true;

        $this->types = new FieldTypeRegistry();
        foreach ($this->types->extend(apply_filters(self::FIELD_TYPES_FILTER, [])) as $error) {
            $this->errors[] = $error;
        }

        $this->buildGlobalPage();
        $this->buildPluginPages();
    }

    /**
     * @return void
     */
    private function buildGlobalPage(): void
    {
        $raw = apply_filters(self::GLOBAL_FILTER, []);
        if (!is_array($raw)) {
            $this->errors[] = sprintf("The '%s' filter must return an array.", self::GLOBAL_FILTER);
            return;
        }

        /** @var array<string, array<string, mixed>> $entries */
        $entries = [];
        foreach ($raw as $index => $entry) {
            try {
                if (!is_array($entry)) {
                    throw new InvalidConfigException('The entry must be an array.');
                }
                Keys::assertKnown($entry, self::GLOBAL_ENTRY_KEYS, 'the global settings entry');
                $plugin = Identifier::assert($this->readString($entry, 'plugin', null), 'plugin');
                $schema = $this->readSchema($entry);
            } catch (InvalidConfigException $e) {
                $this->errors[] = sprintf('Global settings entry #%s: %s', (string)$index, $e->getMessage());
                continue;
            }

            if (isset($entries[$plugin])) {
                $this->pluginError($plugin, 'global settings', 'the plugin is registered more than once; only the first entry is used.');
                continue;
            }

            $entries[$plugin] = $schema;
        }

        // Every failed round removes the plugin responsible for the error and merges the rest again.
        while ($entries !== []) {
            $merge = (new ConfigMerger())->merge($entries);

            if ($merge['orphans'] !== []) {
                $sectionId = (string)array_key_first($merge['orphans']);
                $plugin = $merge['orphans'][$sectionId];
                $this->pluginError(
                    $plugin,
                    'global settings',
                    sprintf("section '%s' is extended, but no active plugin declares it (its 'tab' and 'label').", $sectionId),
                );
                unset($entries[$plugin]);
                continue;
            }

            try {
                $clean = (new Processor())->process($merge['schema'], $this->types()->systemSchema());
                [$schema, $options] = $this->collectGlobalOptions($clean, $merge['owners']);
            } catch (InvalidConfigException $e) {
                $owner = ConfigMerger::ownerOf($e->getPath(), $merge['owners']);
                if ($owner === null || !isset($entries[$owner])) {
                    $this->errors[] = 'The global settings page is disabled: ' . $e->getMessage();
                    return;
                }
                $this->pluginError($owner, 'global settings', $e->getMessage());
                unset($entries[$owner]);
                continue;
            } catch (OptionCollisionException $e) {
                if ($e->plugin === null || !isset($entries[$e->plugin])) {
                    $this->errors[] = 'The global settings page is disabled: ' . $e->getMessage();
                    return;
                }
                $this->pluginError($e->plugin, 'global settings', $e->getMessage());
                unset($entries[$e->plugin]);
                continue;
            }

            foreach ($merge['rejected'] as $plugin => $message) {
                $this->pluginError($plugin, 'global settings', $message);
            }

            if (empty($schema['sections'])) {
                return;
            }

            $title = __('Global Settings', 'modestox-config-processor-wp');
            $this->addPage(
                new PageDefinition(
                    self::GLOBAL_PAGE_SLUG,
                    PageDefinition::LAYOUT_GLOBAL,
                    null,
                    $title,
                    $title,
                    null,
                    'manage_options',
                    'dashicons-admin-settings',
                    $schema,
                ),
                $options,
            );
            return;
        }
    }

    /**
     * @return void
     */
    private function buildPluginPages(): void
    {
        $raw = apply_filters(self::PLUGIN_FILTER, []);
        if (!is_array($raw)) {
            $this->errors[] = sprintf("The '%s' filter must return an array.", self::PLUGIN_FILTER);
            return;
        }

        $seenPlugins = [];
        foreach ($raw as $index => $entry) {
            try {
                $page = $this->readPluginEntry($entry);
            } catch (InvalidConfigException $e) {
                $plugin = is_array($entry) && is_string($entry['plugin'] ?? null) ? $entry['plugin'] : null;
                if ($plugin !== null) {
                    $this->pluginError($plugin, 'settings page', $e->getMessage());
                } else {
                    $this->errors[] = sprintf('Settings page entry #%s: %s', (string)$index, $e->getMessage());
                }
                continue;
            }

            $plugin = $page['plugin'];
            if (isset($seenPlugins[$plugin])) {
                $this->pluginError($plugin, 'settings page', 'the plugin registers more than one settings page; only the first one is used.');
                continue;
            }
            $seenPlugins[$plugin] = true;

            if ($page['page_slug'] === self::GLOBAL_PAGE_SLUG || isset($this->pages[$page['page_slug']])) {
                $this->pluginError(
                    $plugin,
                    'settings page',
                    sprintf("the page slug '%s' is already used by another settings page.", $page['page_slug']),
                );
                continue;
            }

            try {
                $clean = (new Processor())->process($page['schema'], $this->types()->groupedSchema());
                [$schema, $options] = $this->collectPluginOptions($clean, $plugin, $page['page_slug'], $page['option_prefix']);
            } catch (InvalidConfigException|OptionCollisionException $e) {
                $this->pluginError($plugin, 'settings page', $e->getMessage());
                continue;
            }

            $this->addPage(
                new PageDefinition(
                    $page['page_slug'],
                    PageDefinition::LAYOUT_PLUGIN,
                    $plugin,
                    $page['menu_title'],
                    $page['page_title'],
                    $page['parent_slug'],
                    $page['capability'],
                    '',
                    $schema,
                ),
                $options,
            );
        }
    }

    /**
     * Assigns option names to the storable fields of the merged global schema.
     *
     * @param array<string, mixed> $schema
     * @param array<string, string> $owners
     * @return array{0: array<string, mixed>, 1: array<string, OptionDefinition>}
     * @throws OptionCollisionException
     * @throws InvalidConfigException
     */
    private function collectGlobalOptions(array $schema, array $owners): array
    {
        $options = [];
        foreach ($schema['sections'] ?? [] as $sectionId => $section) {
            foreach ($section['groups'] as $groupId => $group) {
                foreach ($group['fields'] as $fieldId => $field) {
                    $renderer = $this->types()->renderer($field['type']);
                    $this->validateFieldDefinition(
                        $renderer,
                        $field,
                        ['sections', (string)$sectionId, 'groups', (string)$groupId, 'fields', (string)$fieldId],
                    );
                    if (!$renderer->storesValue()) {
                        continue;
                    }

                    $owner = ConfigMerger::ownerOf("sections.{$sectionId}.groups.{$groupId}.fields.{$fieldId}", $owners) ?? '';
                    $name = OptionNameBuilder::build(self::GLOBAL_OPTION_PREFIX, (string)$sectionId, (string)$groupId, (string)$fieldId);
                    $this->assertOptionName($name, $options, $owner);

                    $field['_option_name'] = $name;
                    $schema['sections'][$sectionId]['groups'][$groupId]['fields'][$fieldId] = $field;
                    $options[$name] = new OptionDefinition(
                        $name,
                        $field,
                        PageDefinition::globalOptionGroup((string)$sectionId),
                        $owner,
                        "{$sectionId}/{$groupId}/{$fieldId}",
                    );
                }
            }
        }

        return [$schema, $options];
    }

    /**
     * Assigns option names to the storable fields of a plugin page schema.
     *
     * @param array<string, mixed> $schema
     * @param string $plugin
     * @param string $pageSlug
     * @param string $prefix
     * @return array{0: array<string, mixed>, 1: array<string, OptionDefinition>}
     * @throws OptionCollisionException
     * @throws InvalidConfigException
     */
    private function collectPluginOptions(array $schema, string $plugin, string $pageSlug, string $prefix): array
    {
        $options = [];
        foreach ($schema['groups'] as $groupId => $group) {
            foreach ($group['fields'] as $fieldId => $field) {
                $renderer = $this->types()->renderer($field['type']);
                $this->validateFieldDefinition($renderer, $field, ['groups', (string)$groupId, 'fields', (string)$fieldId]);
                if (!$renderer->storesValue()) {
                    continue;
                }

                $name = OptionNameBuilder::build($prefix, (string)$groupId, (string)$fieldId);
                $this->assertOptionName($name, $options, $plugin);

                $field['_option_name'] = $name;
                $schema['groups'][$groupId]['fields'][$fieldId] = $field;
                $options[$name] = new OptionDefinition(
                    $name,
                    $field,
                    PageDefinition::pluginOptionGroup($pageSlug),
                    $plugin,
                    "{$groupId}/{$fieldId}",
                );
            }
        }

        return [$schema, $options];
    }

    /**
     * Runs the field type's own checks and adds the schema path to the error, so the right plugin is blamed.
     *
     * @param AbstractField $renderer
     * @param array<string, mixed> $field
     * @param list<string> $path
     * @return void
     * @throws InvalidConfigException
     */
    private function validateFieldDefinition(AbstractField $renderer, array $field, array $path): void
    {
        try {
            $renderer->validateDefinition($field);
        } catch (InvalidConfigException $e) {
            foreach (array_reverse($path) as $segment) {
                $e->prependPath($segment);
            }
            throw $e;
        }
    }

    /**
     * @param string $name
     * @param array<string, OptionDefinition> $pending Options of the page being built.
     * @param string $owner
     * @return void
     * @throws OptionCollisionException
     */
    private function assertOptionName(string $name, array $pending, string $owner): void
    {
        if (strlen($name) > OptionNameBuilder::MAX_LENGTH) {
            throw new OptionCollisionException(
                sprintf("The option name '%s' is longer than %d characters. Use shorter identifiers.", $name, OptionNameBuilder::MAX_LENGTH),
                $owner,
            );
        }

        $taken = $pending[$name] ?? $this->options[$name] ?? null;
        if ($taken !== null) {
            throw new OptionCollisionException(
                sprintf("The option name '%s' is already used by plugin '%s' (field '%s').", $name, $taken->plugin, $taken->path),
                $owner,
            );
        }
    }

    /**
     * @param PageDefinition $page
     * @param array<string, OptionDefinition> $options
     * @return void
     */
    private function addPage(PageDefinition $page, array $options): void
    {
        $this->pages[$page->slug] = $page;
        foreach ($options as $name => $option) {
            $this->options[$name] = $option;
            $this->index[$option->plugin][$option->path] = $name;
        }
    }

    /**
     * @param mixed $entry
     * @return array{plugin: string, page_slug: string, schema: array<string, mixed>, parent_slug: string, menu_title: string, page_title: string, capability: string, option_prefix: string}
     * @throws InvalidConfigException
     */
    private function readPluginEntry(mixed $entry): array
    {
        if (!is_array($entry)) {
            throw new InvalidConfigException('The entry must be an array.');
        }

        Keys::assertKnown($entry, self::PLUGIN_ENTRY_KEYS, 'the settings page entry');

        $plugin = Identifier::assert($this->readString($entry, 'plugin', null), 'plugin');

        $pageSlug = $this->readString($entry, 'page_slug', null);
        if (!preg_match(self::PAGE_SLUG_PATTERN, $pageSlug)) {
            throw new InvalidConfigException(
                sprintf("The 'page_slug' value '%s' may contain only lower-case letters, digits, '_' and '-'.", $pageSlug),
            );
        }

        $schema = $this->readSchema($entry);
        $menuTitle = $this->readString($entry, 'menu_title', $plugin);

        $prefix = Identifier::assert($this->readString($entry, 'option_prefix', $plugin), 'option prefix');
        if ($prefix === self::GLOBAL_OPTION_PREFIX || str_starts_with($prefix, self::GLOBAL_OPTION_PREFIX . '_')) {
            throw new InvalidConfigException(
                sprintf("The option prefix '%s' is reserved for the global settings page. Set 'option_prefix' explicitly.", $prefix),
            );
        }

        return [
            'plugin'        => $plugin,
            'page_slug'     => $pageSlug,
            'schema'        => $schema,
            'parent_slug'   => $this->readString($entry, 'parent_slug', 'options-general.php'),
            'menu_title'    => $menuTitle,
            'page_title'    => $this->readString($entry, 'page_title', $menuTitle),
            'capability'    => $this->readString($entry, 'capability', 'manage_options'),
            'option_prefix' => $prefix,
        ];
    }

    /**
     * @param array<string, mixed> $entry
     * @param string $key
     * @param string|null $default Null makes the key required.
     * @return string
     * @throws InvalidConfigException
     */
    private function readString(array $entry, string $key, ?string $default): string
    {
        if (!isset($entry[$key])) {
            if ($default === null) {
                throw new InvalidConfigException(sprintf("The '%s' key is required.", $key));
            }
            return $default;
        }

        if (!is_string($entry[$key])) {
            throw new InvalidConfigException(sprintf("The '%s' key must be a string.", $key));
        }

        $value = trim($entry[$key]);
        if ($value === '') {
            throw new InvalidConfigException(sprintf("The '%s' key cannot be empty.", $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    private function readSchema(array $entry): array
    {
        if (!isset($entry['schema']) || !is_array($entry['schema'])) {
            throw new InvalidConfigException("The 'schema' key is required and must be an array.");
        }

        return $entry['schema'];
    }

    /**
     * @param string $plugin
     * @param string $context
     * @param string $message
     * @return void
     */
    private function pluginError(string $plugin, string $context, string $message): void
    {
        $this->errors[] = sprintf("Plugin '%s' (%s): %s", $plugin, $context, $message);
    }
}