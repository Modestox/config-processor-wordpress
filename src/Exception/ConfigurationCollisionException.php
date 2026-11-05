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
 * Thrown when a plugin tries to declare or change a part of the global schema owned by another plugin.
 */
class ConfigurationCollisionException extends \Exception {}