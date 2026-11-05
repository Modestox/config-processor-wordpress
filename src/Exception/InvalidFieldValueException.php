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
 * Class InvalidFieldValueException
 *
 * Thrown by a field type when a submitted or stored value does not fit the field definition.
 */
class InvalidFieldValueException extends \Exception {}