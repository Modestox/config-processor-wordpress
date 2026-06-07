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
 * Generates select input options mapping multiple stored parameters simultaneously.
 */
class MultiSelect extends AbstractField
{
    /**
     * Renders standard multiple entry selection tags.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'regular-text');
        $options = (array)($fieldData['options'] ?? []);

        // Stored arrays inside flat options are serialized or cast internally
        $selectedValues = is_array($attr['value']) ? $attr['value'] : (array)maybe_unserialize($attr['value']);
        ?>
        <select id="<?php echo esc_attr($attr['id']); ?>"
                name="<?php echo esc_attr($attr['option_name']); ?>[]"
                class="<?php echo esc_attr($attr['classes']); ?>"
                multiple="multiple" size="5">
            <?php foreach ($options as $value => $label): ?>
                <option value="<?php echo esc_attr((string)$value); ?>" <?php selected(in_array((string)$value, $selectedValues, true)); ?>>
                    <?php echo esc_html(trim((string)$label)); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}