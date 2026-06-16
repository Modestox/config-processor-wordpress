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
 * Class Password
 *
 * Renders secure password input fields.
 */
class Password extends AbstractField
{
    /**
     * Renders password input HTML markup.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'regular-text');
        ?>
        <input type="password"
               id="<?php echo esc_attr($attr['id']); ?>"
               name="<?php echo esc_attr($attr['option_name']); ?>"
               class="<?php echo esc_attr($attr['classes']); ?>"
               value="<?php echo esc_attr($attr['value']); ?>"
                <?php echo $attr['placeholder'] !== '' ? 'placeholder="' . esc_attr($attr['placeholder']) . '"' : ''; ?> />

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}