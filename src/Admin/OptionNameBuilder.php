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

/**
 * Class OptionNameBuilder
 *
 * Handles standardized generation of database option keys.
 */
class OptionNameBuilder
{
    /**
     * Builds a sanitized unique option key.
     *
     * @param string $prefix
     * @param string $group
     * @param string $field
     * @return string
     */
    public static function build(string $prefix, string $group, string $field): string
    {
        $parts = array_filter([$prefix, $group, $field]);
        return implode('_', array_map('sanitize_key', $parts));
    }
}