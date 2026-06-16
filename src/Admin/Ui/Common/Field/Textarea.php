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
 * Class Textarea
 *
 * Renders standard multi-line text area input fields.
 */
class Textarea extends AbstractField
{
    /**
     * Renders textarea input HTML markup.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'large-text');
        ?>
        <textarea id="<?php echo esc_attr($attr['id']); ?>"
                  name="<?php echo esc_attr($attr['option_name']); ?>"
                  class="<?php echo esc_attr($attr['classes']); ?>"
                  placeholder="<?php echo esc_attr($attr['placeholder']); ?>"
                  rows="5" cols="50"><?php echo esc_html($attr['value']); ?></textarea>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}