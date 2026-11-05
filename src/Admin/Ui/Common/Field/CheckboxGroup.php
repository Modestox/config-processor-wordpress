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
 * Class CheckboxGroup
 *
 * Several choices from 'options' shown as checkboxes. Same value rules as MultiSelect.
 * 'required' is not applied: on a checkbox group the browser would require every box.
 */
class CheckboxGroup extends MultiSelect
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        $selected = $this->selectedMap($value);
        ?>
        <fieldset class="<?php echo esc_attr($this->cssClass($field, 'mtx-checkbox-group-container')); ?>">
            <?php foreach ($this->options($field) as $key => $label): ?>
                <label class="mtx-choice-label">
                    <input type="checkbox"
                           name="<?php echo esc_attr($optionName); ?>[]"
                           value="<?php echo esc_attr((string)$key); ?>"
                           <?php checked(isset($selected[(string)$key])); ?> />
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <?php
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function hasLabelTarget(): bool
    {
        return false;
    }
}