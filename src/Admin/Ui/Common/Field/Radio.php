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
 * Class Radio
 *
 * Single choice from 'options' shown as radio buttons. Same value rules as Select.
 */
class Radio extends Select
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        ?>
        <fieldset class="<?php echo esc_attr($this->cssClass($field, 'mtx-radio-node')); ?>">
            <?php foreach ($this->options($field) as $key => $label): ?>
                <label class="mtx-choice-label">
                    <input type="radio"
                           name="<?php echo esc_attr($optionName); ?>"
                           value="<?php echo esc_attr((string)$key); ?>"
                            <?php checked((string)$key, (string)$value); ?>
                            <?php $this->requiredAttribute($field); ?> />
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