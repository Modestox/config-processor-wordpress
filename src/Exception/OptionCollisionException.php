<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Exception;

/**
 * Class OptionCollisionException
 *
 * Thrown when a field would be stored under an option name that is already taken or too long.
 */
class OptionCollisionException extends \Exception
{
    /**
     * @param string      $message
     * @param string|null $plugin Plugin responsible for the conflicting field, if known.
     */
    public function __construct(string $message, public readonly ?string $plugin = null)
    {
        parent::__construct($message);
    }
}