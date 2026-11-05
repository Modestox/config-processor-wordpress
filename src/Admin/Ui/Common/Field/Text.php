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
 * Single-line text input. Stored as a trimmed string without line breaks.
 */
class Text extends AbstractField
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        ?>
        <input type="text"
               id="<?php echo esc_attr($this->inputId($optionName)); ?>"
               name="<?php echo esc_attr($optionName); ?>"
               class="<?php echo esc_attr($this->cssClass($field, 'regular-text')); ?>"
               value="<?php echo esc_attr((string)$value); ?>"
               placeholder="<?php echo esc_attr((string)($field['placeholder'] ?? '')); ?>"
               <?php $this->requiredAttribute($field); ?> />
        <?php
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function sanitize(mixed $input, array $field, mixed $previous): mixed
    {
        return $this->filterMarkup($this->normalize($input, $field));
    }

    /**
     * @inheritDoc
     */
    protected function normalize(mixed $value, array $field): string
    {
        $value = str_replace(["\r\n", "\r", "\n", "\0"], [' ', ' ', ' ', ''], $this->scalarToString($value));

        return trim($value);
    }
}