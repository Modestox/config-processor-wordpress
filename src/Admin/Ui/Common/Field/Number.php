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
 * Generates html5 specific input text field restricted to numerical boundaries.
 */
class Number extends AbstractField
{
    /**
     * Renders targeted numeric range components.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'small-text');
        $min = isset($fieldData['min']) ? ' min="' . (int)$fieldData['min'] . '"' : '';
        $max = isset($fieldData['max']) ? ' max="' . (int)$fieldData['max'] . '"' : '';
        ?>
        <input type="number"
               id="<?php echo esc_attr($attr['id']); ?>"
               name="<?php echo esc_attr($attr['option_name']); ?>"
               class="<?php echo esc_attr($attr['classes']); ?>"
               value="<?php echo esc_attr($attr['value']); ?>"
                <?php echo $min . $max; ?> />

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}