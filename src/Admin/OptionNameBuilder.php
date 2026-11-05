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

use InvalidArgumentException;

/**
 * Class OptionNameBuilder
 *
 * The single place where option names are built:
 *  - global page:   mtx_{section}_{group}_{field}
 *  - plugin page:   {option_prefix}_{group}_{field}
 *
 * All parts are identifiers already checked against /^[a-z0-9_]+$/, so they are used as they are.
 */
final class OptionNameBuilder
{
    /**
     * Length of the wp_options.option_name column.
     */
    public const MAX_LENGTH = 191;

    /**
     * @param string ...$parts
     * @return string
     */
    public static function build(string ...$parts): string
    {
        foreach ($parts as $part) {
            if ($part === '') {
                throw new InvalidArgumentException('Option name parts cannot be empty.');
            }
        }

        return implode('_', $parts);
    }
}