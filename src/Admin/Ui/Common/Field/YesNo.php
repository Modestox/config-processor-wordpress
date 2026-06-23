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
 * Class YesNo
 *
 * Renders select dropdowns for binary Yes/No choices.
 */
class YesNo extends AbstractField
{
    /**
     * Renders binary selection dropdown HTML markup.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'small-text');
        $selectedValue = (int)$attr['value'];
        ?>
        <select id="<?php echo esc_attr($attr['id']); ?>"
                name="<?php echo esc_attr($attr['option_name']); ?>"
                class="<?php echo esc_attr($attr['classes']); ?>"
                <?php echo $attr['required_attr']; ?>>
            <option value="1" <?php selected($selectedValue, 1); ?>><?php echo esc_html__('Yes', 'modestox-config-processor-wp'); ?></option>
            <option value="0" <?php selected($selectedValue, 0); ?>><?php echo esc_html__('No', 'modestox-config-processor-wp'); ?></option>
        </select>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}