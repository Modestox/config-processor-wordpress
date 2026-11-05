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
 * Multi-line text. Line breaks are kept (normalized to "\n"), the value is trimmed.
 * Markup is kept as is only for users with the 'unfiltered_html' capability.
 */
class Textarea extends AbstractField
{
    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        ?>
        <textarea id="<?php echo esc_attr($this->inputId($optionName)); ?>"
                  name="<?php echo esc_attr($optionName); ?>"
                  class="<?php echo esc_attr($this->cssClass($field, 'large-text')); ?>"
                  placeholder="<?php echo esc_attr((string)($field['placeholder'] ?? '')); ?>"
                  rows="5" cols="50"
                  <?php $this->requiredAttribute($field); ?>><?php echo esc_textarea((string)$value); ?></textarea>
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
        $value = str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $this->scalarToString($value));

        return trim($value);
    }
}