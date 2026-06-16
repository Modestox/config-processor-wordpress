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
 * Class ConfigurationCollisionException
 *
 * Thrown when a duplicate configuration path or plugin identifier collision occurs.
 */
class ConfigurationCollisionException extends \Exception {}