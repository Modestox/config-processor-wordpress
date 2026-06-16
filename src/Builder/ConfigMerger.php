<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Builder;

use Modestox\ConfigProcessorWp\Exception\ConfigurationCollisionException;

/**
 * Class ConfigMerger
 *
 * Merges configuration schemas with validation of unique field paths.
 */
class ConfigMerger
{
    /**
     * Merges multiple configuration payloads into a single unified schema layout.
     *
     * @param array<int, array<string, mixed>> $rawGlobalFilters
     * @return array<string, mixed>
     * @throws ConfigurationCollisionException
     */
    public static function mergeGlobalRegistry(array $rawGlobalFilters): array
    {
        $globalSchema = [
            'tabs'     => [],
            'sections' => [],
        ];

        $pathRegistry = [];
        $registeredPlugins = [];

        foreach ($rawGlobalFilters as $filterPayload) {
            if (!is_array($filterPayload)) {
                continue;
            }

            $pluginSlug = (string)($filterPayload['plugin'] ?? '');
            $pluginConfig = $filterPayload['schema'] ?? null;

            if ($pluginSlug === '' || !is_array($pluginConfig)) {
                continue;
            }

            if (isset($registeredPlugins[$pluginSlug])) {
                throw new ConfigurationCollisionException(
                    sprintf("Plugin identifier '%s' is registered multiple times.", $pluginSlug)
                );
            }
            $registeredPlugins[$pluginSlug] = true;

            // 1. Merge Tabs
            if (isset($pluginConfig['tabs']) && is_array($pluginConfig['tabs'])) {
                $globalSchema['tabs'] = array_merge($globalSchema['tabs'], $pluginConfig['tabs']);
            }

            // 2. Process Sections
            if (isset($pluginConfig['sections']) && is_array($pluginConfig['sections'])) {
                foreach ($pluginConfig['sections'] as $secKey => $secData) {
                    if (!isset($globalSchema['sections'][$secKey])) {
                        $globalSchema['sections'][$secKey] = $secData;
                    } else {
                        foreach ($secData as $metaKey => $metaValue) {
                            if ($metaKey !== 'groups') {
                                $globalSchema['sections'][$secKey][$metaKey] = $metaValue;
                            }
                        }
                    }

                    // 3. Process Groups
                    if (isset($secData['groups']) && is_array($secData['groups'])) {
                        if (!isset($globalSchema['sections'][$secKey]['groups'])) {
                            $globalSchema['sections'][$secKey]['groups'] = [];
                        }

                        foreach ($secData['groups'] as $groupKey => $groupData) {
                            if (!isset($globalSchema['sections'][$secKey]['groups'][$groupKey])) {
                                $globalSchema['sections'][$secKey]['groups'][$groupKey] = $groupData;
                            } else {
                                foreach ($groupData as $metaKey => $metaValue) {
                                    if ($metaKey !== 'fields') {
                                        $globalSchema['sections'][$secKey]['groups'][$groupKey][$metaKey] = $metaValue;
                                    }
                                }
                            }

                            // 4. Process Fields
                            if (isset($groupData['fields']) && is_array($groupData['fields'])) {
                                if (!isset($globalSchema['sections'][$secKey]['groups'][$groupKey]['fields'])) {
                                    $globalSchema['sections'][$secKey]['groups'][$groupKey]['fields'] = [];
                                }

                                foreach ($groupData['fields'] as $fieldKey => $fieldData) {
                                    $pathHash = sprintf('%s/%s/%s', $secKey, $groupKey, $fieldKey);

                                    if (isset($pathRegistry[$pathHash])) {
                                        throw new ConfigurationCollisionException(
                                            sprintf(
                                                "Field path collision detected: '%s' is duplicated by plugin '%s' and plugin '%s'.",
                                                $pathHash,
                                                $pluginSlug,
                                                $pathRegistry[$pathHash]
                                            )
                                        );
                                    }

                                    $pathRegistry[$pathHash] = $pluginSlug;
                                    $globalSchema['sections'][$secKey]['groups'][$groupKey]['fields'][$fieldKey] = $fieldData;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $globalSchema;
    }
}