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

use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessor\Validator\Identifier;
use Modestox\ConfigProcessor\Validator\Keys;
use Modestox\ConfigProcessorWp\Exception\ConfigurationCollisionException;

/**
 * Class ConfigMerger
 *
 * Merges the global schemas of several plugins into one SystemConfig schema.
 *
 * Ownership rules:
 *  - a tab can be declared by one plugin only;
 *  - the metadata of a section or group (everything except 'groups' / 'fields') can be set by one plugin only,
 *    other plugins may add groups to a section and fields to a group;
 *  - a field path can be declared by one plugin only.
 *
 * A plugin whose schema breaks a rule is rejected as a whole; the others are merged without it.
 */
final class ConfigMerger
{
    /**
     * @param array<string, array<string, mixed>> $entries Plugin identifier => raw global schema, in registration order.
     * @return array{schema: array<string, mixed>, owners: array<string, string>, rejected: array<string, string>, orphans: array<string, string>}
     */
    public function merge(array $entries): array
    {
        $state = ['schema' => [], 'owners' => [], 'meta' => []];
        $rejected = [];

        foreach ($entries as $plugin => $schema) {
            $plugin = (string)$plugin;
            $candidate = $state;

            try {
                $this->apply($plugin, $schema, $candidate);
            } catch (ConfigurationCollisionException|InvalidConfigException $e) {
                $rejected[$plugin] = $e->getMessage();
                continue;
            }

            $state = $candidate;
        }

        // Sections that were only extended: nobody declared their tab and label.
        $orphans = [];
        foreach (array_keys($state['schema']['sections'] ?? []) as $sectionId) {
            $path = 'sections.' . $sectionId;
            if (empty($state['meta'][$path])) {
                $orphans[(string)$sectionId] = $state['owners'][$path];
            }
        }

        return [
            'schema'   => $state['schema'],
            'owners'   => $state['owners'],
            'rejected' => $rejected,
            'orphans'  => $orphans,
        ];
    }

    /**
     * Finds the plugin responsible for an error path such as 'sections.api.groups.auth.fields.timeout'.
     *
     * @param string                $path
     * @param array<string, string> $owners
     * @return string|null
     */
    public static function ownerOf(string $path, array $owners): ?string
    {
        $segments = $path === '' ? [] : explode('.', $path);

        while ($segments !== []) {
            $key = implode('.', $segments);
            if (isset($owners[$key])) {
                return $owners[$key];
            }
            array_pop($segments);
        }

        return null;
    }

    /**
     * @param string                                                                                              $plugin
     * @param array<string, mixed>                                                                                $schema
     * @param array{schema: array<string, mixed>, owners: array<string, string>, meta: array<string, bool>} $state
     * @return void
     * @throws ConfigurationCollisionException
     * @throws InvalidConfigException
     */
    private function apply(string $plugin, array $schema, array &$state): void
    {
        Keys::assertKnown($schema, ['tabs', 'sections'], 'the global settings schema');

        foreach (Keys::extensions($schema) as $key => $value) {
            $key = (string)$key;
            if (isset($state['owners'][$key])) {
                throw new ConfigurationCollisionException(
                    sprintf("Root key '%s' is already declared by plugin '%s'.", $key, $state['owners'][$key]),
                );
            }
            $state['owners'][$key] = $plugin;
            $state['schema'][$key] = $value;
        }

        if (isset($schema['tabs'])) {
            foreach ($this->assertArray($schema['tabs'], "The 'tabs' key") as $tabId => $tabData) {
                $tabId = Identifier::assert($tabId, 'tab');
                $path = 'tabs.' . $tabId;

                if (isset($state['owners'][$path])) {
                    throw new ConfigurationCollisionException(
                        sprintf("Tab '%s' is already declared by plugin '%s'.", $tabId, $state['owners'][$path]),
                    );
                }

                $state['owners'][$path] = $plugin;
                $state['schema']['tabs'][$tabId] = $tabData;
            }
        }

        if (!isset($schema['sections'])) {
            return;
        }

        foreach ($this->assertArray($schema['sections'], "The 'sections' key") as $sectionId => $sectionData) {
            $sectionId = Identifier::assert($sectionId, 'section');
            $sectionData = $this->assertArray($sectionData, "Section '{$sectionId}'");
            $sectionPath = 'sections.' . $sectionId;

            $this->mergeNode($state, $sectionPath, $state['schema']['sections'][$sectionId], $sectionData, 'groups', $plugin, "Section '{$sectionId}'");

            if (!isset($sectionData['groups'])) {
                continue;
            }

            foreach ($this->assertArray($sectionData['groups'], "The 'groups' of section '{$sectionId}'") as $groupId => $groupData) {
                $groupId = Identifier::assert($groupId, 'group');
                $groupData = $this->assertArray($groupData, "Group '{$sectionId}/{$groupId}'");
                $groupPath = $sectionPath . '.groups.' . $groupId;

                $this->mergeNode(
                    $state,
                    $groupPath,
                    $state['schema']['sections'][$sectionId]['groups'][$groupId],
                    $groupData,
                    'fields',
                    $plugin,
                    "Group '{$sectionId}/{$groupId}'",
                );

                if (!isset($groupData['fields'])) {
                    continue;
                }

                foreach ($this->assertArray($groupData['fields'], "The 'fields' of group '{$sectionId}/{$groupId}'") as $fieldId => $fieldData) {
                    $fieldId = Identifier::assert($fieldId, 'field');
                    $fieldPath = $groupPath . '.fields.' . $fieldId;

                    if (isset($state['owners'][$fieldPath])) {
                        throw new ConfigurationCollisionException(
                            sprintf(
                                "Field '%s/%s/%s' is already declared by plugin '%s'.",
                                $sectionId,
                                $groupId,
                                $fieldId,
                                $state['owners'][$fieldPath],
                            ),
                        );
                    }

                    $state['owners'][$fieldPath] = $plugin;
                    $state['schema']['sections'][$sectionId]['groups'][$groupId]['fields'][$fieldId] = $fieldData;
                }
            }
        }
    }

    /**
     * Merges the metadata of a section or group and records its owner.
     *
     * @param array{schema: array<string, mixed>, owners: array<string, string>, meta: array<string, bool>} $state
     * @param string                                                                                              $path
     * @param array<string, mixed>|null                                                                           $node     Reference into $state['schema'].
     * @param array<string, mixed>                                                                                $data     Node data of the current plugin.
     * @param string                                                                                              $childKey 'groups' or 'fields'.
     * @param string                                                                                              $plugin
     * @param string                                                                                              $label
     * @return void
     * @throws ConfigurationCollisionException
     */
    private function mergeNode(array &$state, string $path, ?array &$node, array $data, string $childKey, string $plugin, string $label): void
    {
        $node ??= [];
        $meta = array_diff_key($data, [$childKey => true]);

        // The first contributor answers for the node until somebody declares its metadata.
        if (!isset($state['owners'][$path])) {
            $state['owners'][$path] = $plugin;
        }

        if ($meta === []) {
            return;
        }

        if (!empty($state['meta'][$path])) {
            throw new ConfigurationCollisionException(
                sprintf(
                    "%s belongs to plugin '%s'. Other plugins may only add '%s' to it; remove the keys: %s.",
                    $label,
                    $state['owners'][$path],
                    $childKey,
                    implode(', ', array_map('strval', array_keys($meta))),
                ),
            );
        }

        $state['owners'][$path] = $plugin;
        $state['meta'][$path] = true;
        $node = array_merge($node, $meta);
    }

    /**
     * @param mixed  $value
     * @param string $label
     * @return array<int|string, mixed>
     * @throws InvalidConfigException
     */
    private function assertArray(mixed $value, string $label): array
    {
        if (!is_array($value)) {
            throw new InvalidConfigException("{$label} must be an array.");
        }

        return $value;
    }
}