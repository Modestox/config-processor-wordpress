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
 * Class Select
 *
 * Single choice from 'options'. The value is the option key with its original type (int or string).
 */
class Select extends AbstractField
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        ?>
        <select id="<?php echo esc_attr($this->inputId($optionName)); ?>"
                name="<?php echo esc_attr($optionName); ?>"
                class="<?php echo esc_attr($this->cssClass($field, 'regular-text')); ?>"
                <?php $this->requiredAttribute($field); ?>>
            <?php foreach ($this->options($field) as $key => $label): ?>
                <option value="<?php echo esc_attr((string)$key); ?>" <?php selected((string)$key, (string)$value); ?>>
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
    protected function normalize(mixed $value, array $field): int|string
    {
        $key = $this->matchOption($value, (array)($field['options'] ?? []));

        if ($key === null) {
            $this->fail(__('The selected value is not one of the allowed options.', 'modestox-config-processor-wp'));
        }

        return $key;
    }

    /**
     * Options in display order with their labels.
     *
     * @param array<string, mixed> $field
     * @return array<int|string, string>
     */
    protected function options(array $field): array
    {
        return array_map('strval', (array)($field['options'] ?? []));
    }
}