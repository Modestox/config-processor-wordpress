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
 * Class OptionDefinition
 *
 * One WordPress option backed by a schema field.
 */
final class OptionDefinition
{
    /**
     * @param string $name Option name in wp_options.
     * @param array<string, mixed> $field Validated field definition.
     * @param string $group Settings API option group.
     * @param string $plugin Plugin that owns the field.
     * @param string $path Path used by modestox_config(), e.g. 'general/site_name'.
     */
    public function __construct(
        public readonly string $name,
        public readonly array $field,
        public readonly string $group,
        public readonly string $plugin,
        public readonly string $path,
    ) {}
}