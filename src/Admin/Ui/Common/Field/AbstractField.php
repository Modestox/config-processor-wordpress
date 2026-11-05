<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui\Common\Field;

use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessorWp\Exception\InvalidFieldValueException;

/**
 * Class AbstractField
 *
 * Base class of every field type: markup, value normalization and sanitizing.
 *
 * $field is the field definition as returned by Modestox Config Processor, plus '_option_name'.
 *
 *  - normalize(): strict type normalization, used for saved values and for submitted values;
 *  - sanitize():  normalize() plus write-only rules (markup filtering, keeping a password);
 *  - cast():      tolerant read, falls back to the default when the stored value is not valid any more.
 */
abstract class AbstractField
{
    /**
     * Prints the input(s) of the field.
     *
     * @param string               $optionName Option name, also used as the input name.
     * @param array<string, mixed> $field      Field definition.
     * @param mixed                $value      Current value, already cast for this field.
     * @return void
     */
    abstract public function render(string $optionName, array $field, mixed $value): void;

    /**
     * @param mixed                $value
     * @param array<string, mixed> $field
     * @return mixed
     * @throws InvalidFieldValueException
     */
    abstract protected function normalize(mixed $value, array $field): mixed;

    /**
     * Extra checks of the field definition that the library does not know about (custom 'x_' keys).
     * Called once when the schema is loaded; a failure skips the plugin like any schema error.
     *
     * @param array<string, mixed> $field Validated field definition.
     * @return void
     * @throws InvalidConfigException
     */
    public function validateDefinition(array $field): void
    {
    }

    /**
     * Whether the field is stored as an option. Display-only types return false.
     *
     * @return bool
     */
    public function storesValue(): bool
    {
        return true;
    }

    /**
     * Value of the 'type' argument of register_setting().
     *
     * @return string
     */
    public function settingType(): string
    {
        return 'string';
    }

    /**
     * @param array<string, mixed> $field
     * @return mixed
     */
    public function defaultValue(array $field): mixed
    {
        return $field['default'] ?? '';
    }

    /**
     * Cleans a submitted value before it is saved.
     *
     * @param mixed                $input    Submitted value.
     * @param array<string, mixed> $field
     * @param mixed                $previous Current saved value, already cast.
     * @return mixed
     * @throws InvalidFieldValueException
     */
    public function sanitize(mixed $input, array $field, mixed $previous): mixed
    {
        return $this->normalize($input, $field);
    }

    /**
     * Reads a stored value; anything that does not fit the field becomes the default.
     *
     * @param mixed                $stored
     * @param array<string, mixed> $field
     * @return mixed
     */
    final public function cast(mixed $stored, array $field): mixed
    {
        try {
            return $this->normalize($stored, $field);
        } catch (InvalidFieldValueException) {
            return $this->defaultValue($field);
        }
    }

    /**
     * Whether the row label can point at a single input with the id from inputId().
     *
     * @return bool
     */
    public function hasLabelTarget(): bool
    {
        return true;
    }

    /**
     * Whether the field takes the whole row instead of the label/input columns.
     *
     * @return bool
     */
    public function isFullWidth(): bool
    {
        return false;
    }

    /**
     * @param string $optionName
     * @return string
     */
    public function inputId(string $optionName): string
    {
        return 'mtx-field-' . $optionName;
    }

    /**
     * @param array<string, mixed> $field
     * @param string               $base
     * @return string
     */
    protected function cssClass(array $field, string $base): string
    {
        return trim($base . ' ' . (string)($field['class'] ?? ''));
    }

    /**
     * @param array<string, mixed> $field
     * @return void
     */
    protected function requiredAttribute(array $field): void
    {
        if (!empty($field['required'])) {
            echo ' required="required"';
        }
    }

    /**
     * @param array<string, mixed> $field
     * @return void
     */
    protected function renderComment(array $field): void
    {
        $comment = (string)($field['comment'] ?? '');
        if ($comment !== '') {
            printf('<p class="description mtx-sys-config-field-comment">%s</p>', esc_html($comment));
        }
    }

    /**
     * Turns a scalar into a string; anything else fails.
     *
     * @param mixed $value
     * @return string
     * @throws InvalidFieldValueException
     */
    protected function scalarToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        throw new InvalidFieldValueException(__('The value must be text.', 'modestox-config-processor-wp'));
    }

    /**
     * Users without the 'unfiltered_html' capability may only save post-safe markup.
     *
     * @param string $value
     * @return string
     */
    protected function filterMarkup(string $value): string
    {
        if (!str_contains($value, '<') || current_user_can('unfiltered_html')) {
            return $value;
        }

        return wp_kses_post($value);
    }

    /**
     * Finds the option key that matches a submitted or stored value.
     *
     * @param mixed                     $value
     * @param array<int|string, string> $options
     * @return int|string|null
     */
    protected function matchOption(mixed $value, array $options): int|string|null
    {
        if (is_bool($value)) {
            $value = (int)$value;
        }

        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        foreach (array_keys($options) as $key) {
            if ((string)$key === (string)$value) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param string $message
     * @return never
     * @throws InvalidFieldValueException
     */
    protected function fail(string $message): never
    {
        throw new InvalidFieldValueException($message);
    }
}