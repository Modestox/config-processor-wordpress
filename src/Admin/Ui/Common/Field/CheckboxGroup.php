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
 * Renders grouped input checkbox lists.
 */
class CheckboxGroup extends AbstractField
{
    /**
     * Renders administrative sets of checklist boxes.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'mtx-checkbox-group-container');
        $options = (array)($fieldData['options'] ?? []);
        $selectedValues = is_array($attr['value']) ? $attr['value'] : (array)maybe_unserialize($attr['value']);
        ?>
        <fieldset class="<?php echo esc_attr($attr['classes']); ?>">
            <?php foreach ($options as $value => $label): ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox"
                           name="<?php echo esc_attr($attr['option_name']); ?>[]"
                           value="<?php echo esc_attr((string)$value); ?>"
                            <?php checked(in_array((string)$value, $selectedValues, true)); ?> />
                    <?php echo esc_html(trim((string)$label)); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}