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
 * Class Number
 *
 * Integer or float input with optional min/max.
 * The field accepts fractions only when 'default', 'min' or 'max' is a float.
 */
class Number extends AbstractField
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        ?>
        <input type="number"
               id="<?php echo esc_attr($this->inputId($optionName)); ?>"
               name="<?php echo esc_attr($optionName); ?>"
               class="<?php echo esc_attr($this->cssClass($field, 'small-text')); ?>"
               value="<?php echo esc_attr((string)$value); ?>"
               step="<?php echo $this->isIntegerOnly($field) ? '1' : 'any'; ?>"
               <?php if (isset($field['min'])): ?>min="<?php echo esc_attr((string)$field['min']); ?>"<?php endif; ?>
               <?php if (isset($field['max'])): ?>max="<?php echo esc_attr((string)$field['max']); ?>"<?php endif; ?>
               <?php $this->requiredAttribute($field); ?> />
        <?php
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function settingType(): string
    {
        return 'number';
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(array $field): mixed
    {
        return $field['default'] ?? 0;
    }

    /**
     * @inheritDoc
     */
    protected function normalize(mixed $value, array $field): int|float
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return $this->defaultValue($field);
            }
            if (preg_match('/^[+-]?\d+$/D', $value)) {
                $value = (int)$value;
            } elseif (is_numeric($value)) {
                $value = (float)$value;
            }
        }

        if ($value === null) {
            return $this->defaultValue($field);
        }

        if (!is_int($value) && !is_float($value)) {
            $this->fail(__('The value must be a number.', 'modestox-config-processor-wp'));
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                $this->fail(__('The value must be a finite number.', 'modestox-config-processor-wp'));
            }
            if ($this->isIntegerOnly($field)) {
                if (floor($value) !== $value || abs($value) > PHP_INT_MAX) {
                    $this->fail(__('The value must be a whole number.', 'modestox-config-processor-wp'));
                }
                $value = (int)$value;
            }
        }

        if (isset($field['min']) && $value < $field['min']) {
            /* translators: %s: minimum value */
            $this->fail(sprintf(__('The value cannot be less than %s.', 'modestox-config-processor-wp'), (string)$field['min']));
        }

        if (isset($field['max']) && $value > $field['max']) {
            /* translators: %s: maximum value */
            $this->fail(sprintf(__('The value cannot be greater than %s.', 'modestox-config-processor-wp'), (string)$field['max']));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $field
     * @return bool
     */
    private function isIntegerOnly(array $field): bool
    {
        foreach (['default', 'min', 'max'] as $key) {
            if (is_float($field[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}