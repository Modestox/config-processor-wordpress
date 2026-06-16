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

use Modestox\ConfigProcessor\Processor;
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessorWp\Admin\Ui\Builder;
use Modestox\ConfigProcessorWp\Exception\OptionCollisionException;
use Modestox\ConfigProcessorWp\Exception\ConfigurationCollisionException;

/**
 * Class PageRenderer
 *
 * Validates schemas and routes individual components to build target layouts.
 */
class PageRenderer
{
    private static bool $registered = false;
    private array $allConfigs = [];

    /**
     * PageRenderer constructor.
     *
     * @param array<string, array<string, mixed>> $globalConfigs
     * @param array<string, array<string, mixed>> $pluginConfigs
     * @throws ConfigurationCollisionException
     */
    public function __construct(array $globalConfigs, array $pluginConfigs)
    {
        foreach ($globalConfigs as $slug => $data) {
            $this->allConfigs[$slug] = $data;
        }

        foreach ($pluginConfigs as $slug => $data) {
            if (isset($this->allConfigs[$slug])) {
                throw new ConfigurationCollisionException("Page slug '{$slug}' already registered.");
            }
            $this->allConfigs[$slug] = $data;
        }

        if (!self::$registered) {
            add_action('admin_init', [$this, 'registerSystemSettings']);
            self::$registered = true;
        }
    }

    /**
     * Iterates through all registered schemas to initialize WordPress settings.
     *
     * @return void
     * @throws OptionCollisionException
     */
    public function registerSystemSettings(): void
    {
        static $registeredPaths = [];

        foreach ($this->allConfigs as $pageSlug => $pageData) {
            $prefix = isset($pageData['option_prefix']) ? $this->sanitizePrefix($pageData['option_prefix']) : '';
            $basePrefix = $prefix !== '' ? $prefix : 'mtx_sys_config';
            $schema = (array)($pageData['schema'] ?? []);

            if (isset($schema['sections'])) {
                foreach ($schema['sections'] as $sKey => $section) {
                    $optionGroup = 'mtx_group_' . $pageSlug . '_' . $sKey;
                    $namespace = $basePrefix . '_' . $sKey;

                    foreach ($section['groups'] ?? [] as $gKey => $group) {
                        $this->registerFields($group['fields'] ?? [], $namespace, (string)$gKey, $optionGroup, $registeredPaths);
                    }
                }
            } elseif (isset($schema['groups'])) {
                $optionGroup = 'mtx_group_' . $pageSlug;
                foreach ($schema['groups'] as $gKey => $group) {
                    $this->registerFields($group['fields'] ?? [], $prefix, (string)$gKey, $optionGroup, $registeredPaths);
                }
            }
        }
    }

    /**
     * Registers individual fields using the OptionNameBuilder.
     *
     * @param array<string, mixed> $fields
     * @param string $namespace
     * @param string $groupKey
     * @param string $optionGroup
     * @param array<string, bool> $registeredPaths
     * @return void
     * @throws OptionCollisionException
     */
    private function registerFields(
        array $fields,
        string $namespace,
        string $groupKey,
        string $optionGroup,
        array &$registeredPaths,
    ): void {
        foreach (array_keys($fields) as $fieldKey) {
            $optionName = OptionNameBuilder::build($namespace, $groupKey, (string)$fieldKey);

            if (isset($registeredPaths[$optionName])) {
                throw new OptionCollisionException("Collision detected: Path '{$optionName}' is already occupied.");
            }

            $registeredPaths[$optionName] = true;
            register_setting($optionGroup, $optionName);
        }
    }

    /**
     * Validates layout schema configuration and executes administrative UI rendering.
     *
     * @return void
     */
    public function render(): void
    {
        $currentPage = sanitize_key((string)($_GET['page'] ?? ''));
        $pageData = $this->allConfigs[$currentPage] ?? null;

        if (!$pageData || !isset($pageData['schema'])) {
            wp_die(
                sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__('Modestox Core Error:', 'modestox-config-processor-wp'),
                    esc_html__('Schema not found.', 'modestox-config-processor-wp'),
                ),
            );
        }

        $schema = (array)$pageData['schema'];

        try {
            $processor = new Processor();

            $cleanSchema = isset($schema['sections'])
                ? $processor->process($schema, new SystemConfig())
                : $processor->process($schema, new GroupedConfig());

            (new Builder($cleanSchema, [$pageData], $currentPage))->render();
        } catch (InvalidConfigException $e) {
            wp_die(
                sprintf(
                    '<h3>%s</h3><p>%s</p>',
                    esc_html__('Schema Validation Error:', 'modestox-config-processor-wp'),
                    esc_html($e->getMessage()),
                ),
            );
        }
    }

    /**
     * Sanitizes configuration options prefix string.
     *
     * @param string $prefix
     * @return string
     */
    private function sanitizePrefix(string $prefix): string
    {
        return sanitize_key(rtrim(trim($prefix), '_'));
    }
}