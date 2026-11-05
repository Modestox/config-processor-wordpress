<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin\Ui\Common;

use Modestox\ConfigProcessorWp\Field\FieldTypeRegistry;

/**
 * Class FieldRender
 *
 * Prints the form-table rows of one group.
 */
final class FieldRender
{
    private readonly DependencyResolver $dependencies;

    /**
     * @param array<string, array<string, mixed>> $fields Fields of one group.
     * @param FieldTypeRegistry                   $types
     */
    public function __construct(
        private readonly array $fields,
        private readonly FieldTypeRegistry $types,
    ) {
        $this->dependencies = new DependencyResolver($fields, $types);
    }

    /**
     * @return void
     */
    public function render(): void
    {
        foreach ($this->fields as $key => $field) {
            $this->renderRow((string)$key, $field);
        }
    }

    /**
     * @param string               $key
     * @param array<string, mixed> $field
     * @return void
     */
    private function renderRow(string $key, array $field): void
    {
        $renderer = $this->types->renderer((string)$field['type']);
        $optionName = (string)($field['_option_name'] ?? '');
        $label = (string)($field['label'] ?? $key);

        $classes = ['mtx-field-row', 'mtx-field-type-' . $field['type']];
        $attributes = ' data-mtx-field="' . esc_attr($key) . '"';
        if ($optionName !== '') {
            $attributes .= ' data-mtx-option="' . esc_attr($optionName) . '"';
        }

        $rules = $this->dependencies->clientRules($field);
        if ($rules !== []) {
            $classes[] = 'mtx-dependent-row';
            $attributes .= ' data-depends="' . esc_attr((string)wp_json_encode($rules)) . '"';
            if (!$this->dependencies->isVisible($key)) {
                $attributes .= ' style="display: none;"';
            }
        }

        $value = $this->dependencies->value($key);
        ?>
        <tr class="<?php echo esc_attr(implode(' ', $classes)); ?>"<?php echo $attributes; ?>>
            <?php if ($renderer->isFullWidth()): ?>
                <td colspan="2">
                    <?php if ($label !== $key): ?>
                        <strong class="mtx-full-width-label"><?php echo esc_html($label); ?></strong>
                    <?php endif; ?>
                    <?php $renderer->render($optionName, $field, $value); ?>
                </td>
            <?php else: ?>
                <th scope="row">
                    <?php if ($renderer->hasLabelTarget() && $optionName !== ''): ?>
                        <label for="<?php echo esc_attr($renderer->inputId($optionName)); ?>"><?php echo esc_html($label); ?></label>
                    <?php else: ?>
                        <?php echo esc_html($label); ?>
                    <?php endif; ?>
                </th>
                <td>
                    <?php $renderer->render($optionName, $field, $value); ?>
                </td>
            <?php endif; ?>
        </tr>
        <?php
    }
}