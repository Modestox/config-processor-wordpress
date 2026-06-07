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
 * Generates boolean checkbox toggle flags with fallback state assurance.
 */
class BooleanToggle extends AbstractField
{
    /**
     * Renders standard checkbox inputs matching true/false states perfectly.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'mtx-toggle-checkbox');
        $checked = filter_var($attr['value'], FILTER_VALIDATE_BOOLEAN);
        ?>
        <fieldset style="border:none; padding:0; margin:0;">
            <input type="hidden" name="<?php echo esc_attr($attr['option_name']); ?>" value="0" />
            <label>
                <input type="checkbox"
                       id="<?php echo esc_attr($attr['id']); ?>"
                       name="<?php echo esc_attr($attr['option_name']); ?>"
                       class="<?php echo esc_attr($attr['classes']); ?>"
                       value="1"
                        <?php checked($checked); ?> />
                <?php if ($attr['comment'] !== ''): ?>
                    <span class="description"><?php echo esc_html($attr['comment']); ?></span>
                <?php endif; ?>
            </label>
        </fieldset>
        <?php
    }
}