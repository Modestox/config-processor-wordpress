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
 * The saved password is never printed into the page. An empty submission keeps the saved value.
 */
class Password extends Text
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        $hasValue = (string)$value !== '';
        ?>
        <input type="password"
               id="<?php echo esc_attr($this->inputId($optionName)); ?>"
               name="<?php echo esc_attr($optionName); ?>"
               class="<?php echo esc_attr($this->cssClass($field, 'regular-text')); ?>"
               value=""
               autocomplete="new-password"
               placeholder="<?php echo esc_attr($hasValue ? '••••••••' : ''); ?>"
                <?php if (!$hasValue) {
                    $this->requiredAttribute($field);
                } ?> />
        <?php if ($hasValue): ?>
        <p class="description mtx-sys-config-field-comment">
            <?php echo esc_html__('A password is saved. Leave the field empty to keep it.', 'modestox-config-processor-wp'); ?>
        </p>
    <?php endif;
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function sanitize(mixed $input, array $field, mixed $previous): mixed
    {
        $value = $this->normalize($input, $field);

        return $value === '' ? (string)$previous : $value;
    }
}