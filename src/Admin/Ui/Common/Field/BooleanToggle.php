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
 * Class BooleanToggle
 *
 * Checkbox for the 'boolean' type. The value is a strict bool.
 */
class BooleanToggle extends AbstractField
{
    private const TRUE_VALUES = ['1', 'true', 'on', 'yes'];
    private const FALSE_VALUES = ['0', 'false', 'off', 'no', ''];

    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        ?>
        <fieldset class="mtx-boolean-field">
            <input type="hidden" name="<?php echo esc_attr($optionName); ?>" value="0" />
            <label>
                <input type="checkbox"
                       id="<?php echo esc_attr($this->inputId($optionName)); ?>"
                       name="<?php echo esc_attr($optionName); ?>"
                       class="<?php echo esc_attr($this->cssClass($field, 'mtx-toggle-checkbox')); ?>"
                       value="1"
                       <?php checked($value === true); ?>
                       <?php $this->requiredAttribute($field); ?> />
                <?php $comment = (string)($field['comment'] ?? ''); ?>
                <?php if ($comment !== ''): ?>
                    <span class="description"><?php echo esc_html($comment); ?></span>
                <?php endif; ?>
            </label>
        </fieldset>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function settingType(): string
    {
        return 'boolean';
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(array $field): mixed
    {
        return (bool)($field['default'] ?? false);
    }

    /**
     * @inheritDoc
     */
    protected function normalize(mixed $value, array $field): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null) {
            return false;
        }

        if (is_int($value) && ($value === 0 || $value === 1)) {
            return $value === 1;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));
            if (in_array($value, self::TRUE_VALUES, true)) {
                return true;
            }
            if (in_array($value, self::FALSE_VALUES, true)) {
                return false;
            }
        }

        $this->fail(__('The value must be on or off.', 'modestox-config-processor-wp'));
    }
}