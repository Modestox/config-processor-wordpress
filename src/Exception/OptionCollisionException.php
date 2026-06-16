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
 * Thrown when a unique option name collision is detected during field registration.
 */
class OptionCollisionException extends \Exception {}