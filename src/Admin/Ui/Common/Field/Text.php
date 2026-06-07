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
 * Class Text
 *
 * Generates standard single-line administrative text input elements.
 */
class Text extends AbstractField
{
    /**
     * Renders the explicit text element input layout using pre-computed attributes.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'regular-text');
        ?>
        <input type="text"
               id="<?php echo esc_attr($attr['id']); ?>"
               name="<?php echo esc_attr($attr['option_name']); ?>"
               class="<?php echo esc_attr($attr['classes']); ?>"
               placeholder="<?php echo esc_attr($attr['placeholder']); ?>"
               value="<?php echo esc_attr($attr['value']); ?>"/>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-config-field-comment">
            <?php echo esc_html($attr['comment']); ?>
        </p>
    <?php endif; ?>
        <?php
    }
}