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
 * Renders standard radio button option lists.
 */
class Radio extends AbstractField
{
    /**
     * Renders standard radio input HTML markup.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'mtx-radio-node');
        $options = (array)($fieldData['options'] ?? []);
        ?>
        <fieldset class="<?php echo esc_attr($attr['classes']); ?>">
            <?php foreach ($options as $value => $label): ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="radio"
                           name="<?php echo esc_attr($attr['option_name']); ?>"
                           value="<?php echo esc_attr((string)$value); ?>"
                            <?php checked($attr['value'], (string)$value); ?>
                            <?php echo $attr['required_attr']; ?> />
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