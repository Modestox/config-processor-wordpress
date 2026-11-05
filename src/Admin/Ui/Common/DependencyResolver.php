<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui\Common;

use Modestox\ConfigProcessorWp\Field\FieldTypeRegistry;

/**
 * Class DependencyResolver
 *
 * Evaluates 'depends' of the fields of one group on the server and prepares the same rules for admin-depends.js.
 *
 * A field is visible when every parent is visible and has the expected value.
 * Values are compared as strings ('1'/'0' for booleans), numbers numerically,
 * and for multiselect/checkbox parents the expected value must be one of the selected options.
 */
final class DependencyResolver
{
    /**
     * @var array<string, mixed>
     */
    private array $values = [];

    /**
     * @var array<string, bool>
     */
    private array $visible = [];

    /**
     * @param array<string, array<string, mixed>> $fields Fields of one group.
     * @param FieldTypeRegistry                   $types
     */
    public function __construct(
        private readonly array $fields,
        private readonly FieldTypeRegistry $types,
    ) {}

    /**
     * Current value of a field of the group, cast for its type.
     *
     * @param string $key
     * @return mixed
     */
    public function value(string $key): mixed
    {
        if (!array_key_exists($key, $this->values)) {
            $field = $this->fields[$key] ?? null;
            $optionName = (string)($field['_option_name'] ?? '');

            if ($field === null || $optionName === '') {
                $this->values[$key] = null;
            } else {
                $renderer = $this->types->renderer((string)$field['type']);
                $this->values[$key] = $renderer->cast(get_option($optionName, $renderer->defaultValue($field)), $field);
            }
        }

        return $this->values[$key];
    }

    /**
     * @param string       $key
     * @param list<string> $trail Fields already on the path (protection against cycles).
     * @return bool
     */
    public function isVisible(string $key, array $trail = []): bool
    {
        if (isset($this->visible[$key])) {
            return $this->visible[$key];
        }

        if (in_array($key, $trail, true)) {
            return true;
        }

        $depends = $this->fields[$key]['depends'] ?? null;
        $result = true;

        if (is_array($depends)) {
            foreach ($depends as $parentKey => $expected) {
                $parentKey = (string)$parentKey;
                $parent = $this->fields[$parentKey] ?? null;
                if ($parent === null) {
                    continue;
                }

                if (
                    !$this->isVisible($parentKey, [...$trail, $key])
                    || !self::matches((string)$parent['type'], $this->value($parentKey), $expected)
                ) {
                    $result = false;
                    break;
                }
            }
        }

        return $this->visible[$key] = $result;
    }

    /**
     * Rules for admin-depends.js: the parent is addressed by its full option name.
     *
     * @param array<string, mixed> $field
     * @return list<array{option: string, type: string, value: string}>
     */
    public function clientRules(array $field): array
    {
        $depends = $field['depends'] ?? null;
        if (!is_array($depends)) {
            return [];
        }

        $rules = [];
        foreach ($depends as $parentKey => $expected) {
            $parent = $this->fields[(string)$parentKey] ?? null;
            $optionName = (string)($parent['_option_name'] ?? '');
            if ($parent === null || $optionName === '') {
                continue;
            }

            $rules[] = [
                'option' => $optionName,
                'type'   => (string)$parent['type'],
                'value'  => self::toComparable($expected),
            ];
        }

        return $rules;
    }

    /**
     * @param string $parentType
     * @param mixed  $current
     * @param mixed  $expected
     * @return bool
     */
    public static function matches(string $parentType, mixed $current, mixed $expected): bool
    {
        $expected = self::toComparable($expected);

        if (is_array($current)) {
            return in_array($expected, array_map([self::class, 'toComparable'], $current), true);
        }

        $current = self::toComparable($current);

        if ($parentType === 'number' && is_numeric($current) && is_numeric($expected)) {
            return (float)$current === (float)$expected;
        }

        return $current === $expected;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function toComparable(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string)$value : '';
    }
}