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
 * Performs lightning-fast, flat configuration merges with strict unique machine path validation.
 */
class ConfigMerger
{
    /**
     * Aggregates configuration streams and throws an exception strictly on duplicate field paths.
     * Presentation metadata conflicts (labels, icons) are naturally overwritten by the trailing plugin.
     *
     * @param array<string, mixed> $rawGlobalFilters Raw array of structures aggregated from the filter stream.
     * @return array<string, mixed> Complete validated unified schema matrix.
     * @throws ConfigurationCollisionException If an identical section/group/field path key is detected twice.
     */
    public static function mergeGlobalRegistry(array $rawGlobalFilters): array
    {
        $globalSchema = [
            'tabs'     => [],
            'sections' => [],
        ];

        // Flat lookup map to enforce unique field coordinates: 'sec/group/field' => pluginSlug
        $pathRegistry = [];

        foreach ($rawGlobalFilters as $pluginSlug => $pluginConfig) {
            if (!is_array($pluginConfig)) {
                continue;
            }

            // 1. Merge Tabs (Last one naturally updates labels/icons)
            if (isset($pluginConfig['tabs']) && is_array($pluginConfig['tabs'])) {
                $globalSchema['tabs'] = array_merge($globalSchema['tabs'], $pluginConfig['tabs']);
            }

            // 2. Process Sections
            if (isset($pluginConfig['sections']) && is_array($pluginConfig['sections'])) {
                foreach ($pluginConfig['sections'] as $secKey => $secData) {
                    if (!isset($globalSchema['sections'][$secKey])) {
                        $globalSchema['sections'][$secKey] = $secData;
                    } else {
                        // Section already exists. Overwrite top-level metadata (label, tab, sort_order)
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
                                // Group already exists. Overwrite its metadata (label, sort_order)
                                foreach ($groupData as $metaKey => $metaValue) {
                                    if ($metaKey !== 'fields') {
                                        $globalSchema['sections'][$secKey]['groups'][$groupKey][$metaKey] = $metaValue;
                                    }
                                }
                            }

                            // 4. Process Fields with Absolute Unique Guardrail
                            if (isset($groupData['fields']) && is_array($groupData['fields'])) {
                                if (!isset($globalSchema['sections'][$secKey]['groups'][$groupKey]['fields'])) {
                                    $globalSchema['sections'][$secKey]['groups'][$groupKey]['fields'] = [];
                                }

                                foreach ($groupData['fields'] as $fieldKey => $fieldData) {
                                    $pathHash = sprintf('%s/%s/%s', $secKey, $groupKey, $fieldKey);

                                    // If this exact coordinate already exists — we have a real collision
                                    if (isset($pathRegistry[$pathHash])) {
                                        throw new ConfigurationCollisionException(
                                            sprintf(
                                                "Configuration field collision detected! Unique field path '%s' is duplicated by plugin '%s' and plugin '%s'.",
                                                $pathHash,
                                                $pluginSlug,
                                                $pathRegistry[$pathHash],
                                            ),
                                        );
                                    }

                                    // Register the path and save the field definition
                                    $pathRegistry[$pathHash] = (string)$pluginSlug;
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