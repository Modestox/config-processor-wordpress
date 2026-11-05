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

/**
 * Class MultiSelect
 *
 * Several choices from 'options'. The value is a list of option keys in the order of 'options'.
 * Nothing selected is an empty list.
 */
class MultiSelect extends AbstractField
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        $selected = $this->selectedMap($value);
        $options = $this->options($field);
        ?>
        <select id="<?php echo esc_attr($this->inputId($optionName)); ?>"
                name="<?php echo esc_attr($optionName); ?>[]"
                class="<?php echo esc_attr($this->cssClass($field, 'regular-text')); ?>"
                multiple="multiple"
                size="<?php echo esc_attr((string)min(max(count($options), 2), 8)); ?>"
                <?php $this->requiredAttribute($field); ?>>
            <?php foreach ($options as $key => $label): ?>
                <option value="<?php echo esc_attr((string)$key); ?>" <?php selected(isset($selected[(string)$key])); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function settingType(): string
    {
        return 'array';
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(array $field): mixed
    {
        return (array)($field['default'] ?? []);
    }

    /**
     * @inheritDoc
     * @return list<int|string>
     */
    protected function normalize(mixed $value, array $field): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $this->fail(__('The value must be a list of options.', 'modestox-config-processor-wp'));
        }

        $options = (array)($field['options'] ?? []);
        $chosen = [];
        foreach ($value as $item) {
            if ($item === '') {
                continue;
            }
            $key = $this->matchOption($item, $options);
            if ($key === null) {
                $this->fail(__('One of the selected values is not an allowed option.', 'modestox-config-processor-wp'));
            }
            $chosen[(string)$key] = true;
        }

        $result = [];
        foreach (array_keys($options) as $key) {
            if (isset($chosen[(string)$key])) {
                $result[] = $key;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<int|string, string>
     */
    protected function options(array $field): array
    {
        return array_map('strval', (array)($field['options'] ?? []));
    }

    /**
     * @param mixed $value
     * @return array<string, true>
     */
    protected function selectedMap(mixed $value): array
    {
        $map = [];
        foreach ((array)$value as $key) {
            if (is_scalar($key)) {
                $map[(string)$key] = true;
            }
        }

        return $map;
    }
}