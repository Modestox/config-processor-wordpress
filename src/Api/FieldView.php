<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Api;

use Modestox\ConfigProcessorWp\Admin\Assets;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\AbstractField;
use Modestox\ConfigProcessorWp\Exception\InvalidFieldValueException;

/**
 * Class FieldView
 *
 * One validated field for use outside the settings pages (custom forms, CRUD screens).
 * Created by FormFields::field(). Public API: the methods below keep their signatures within a major version.
 *
 *     $field = modestox_fields()->field('select', 'status', ['label' => 'Status', 'options' => [...]]);
 *     $field->renderRow($note['status'] ?? null);              // <tr> with label and input
 *     $clean = $field->sanitize(wp_unslash($_POST['status'] ?? null), $note['status'] ?? null);
 */
final class FieldView
{
    /**
     * @param AbstractField        $renderer
     * @param string               $name   HTML name of the input, e.g. 'status' or 'note[status]'.
     * @param array<string, mixed> $field  Definition normalized by Modestox Config Processor.
     * @param Assets               $assets
     */
    public function __construct(
        private readonly AbstractField $renderer,
        private readonly string $name,
        private readonly array $field,
        private readonly Assets $assets,
    ) {}

    /**
     * HTML name of the input (as passed to FormFields::field()).
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * HTML id of the input, for <label for="...">. Brackets of the name are replaced: 'note[status]' -> 'mtx-field-note_status_'.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->renderer->inputId($this->name);
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return (string)$this->field['type'];
    }

    /**
     * Label from the definition; the library falls back to the field key.
     *
     * @return string
     */
    public function label(): string
    {
        return (string)$this->field['label'];
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return (bool)($this->field['required'] ?? false);
    }

    /**
     * Whether a <label for> can point at a single input (false for radio, checkbox, dynamic rows, media, infoblock).
     *
     * @return bool
     */
    public function hasLabelTarget(): bool
    {
        return $this->renderer->hasLabelTarget();
    }

    /**
     * Normalized definition (every key filled in by the library).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function defaultValue(): mixed
    {
        return $this->renderer->defaultValue($this->field);
    }

    /**
     * Typed value: null -> default, anything that does not fit the field -> default.
     *
     * @param mixed $stored
     * @return mixed
     */
    public function cast(mixed $stored): mixed
    {
        return $stored === null ? $this->defaultValue() : $this->renderer->cast($stored, $this->field);
    }

    /**
     * Prints the input(s). Styles and scripts are loaded automatically.
     *
     * @param mixed $value Current value; null shows the default.
     * @return void
     */
    public function render(mixed $value = null): void
    {
        $this->assets->enqueue();
        $this->renderer->render($this->name, $this->field, $this->cast($value));
    }

    /**
     * Same as render(), returned as a string.
     *
     * @param mixed $value
     * @return string
     */
    public function html(mixed $value = null): string
    {
        ob_start();
        $this->render($value);

        return (string)ob_get_clean();
    }

    /**
     * Prints a form-table row: label (with a "*" for required fields) and the input.
     *
     * @param mixed $value
     * @return void
     */
    public function renderRow(mixed $value = null): void
    {
        $required = $this->isRequired() ? ' <span class="required" aria-hidden="true">*</span>' : '';
        ?>
        <tr class="mtx-field-row mtx-field-type-<?php echo esc_attr($this->type()); ?>">
            <?php if ($this->renderer->isFullWidth()): ?>
                <td colspan="2"><?php $this->render($value); ?></td>
            <?php else: ?>
                <th scope="row">
                    <?php if ($this->hasLabelTarget()): ?>
                        <label for="<?php echo esc_attr($this->id()); ?>"><?php echo esc_html($this->label()); ?></label><?php echo $required; ?>
                    <?php else: ?>
                        <?php echo esc_html($this->label()); ?><?php echo $required; ?>
                    <?php endif; ?>
                </th>
                <td><?php $this->render($value); ?></td>
            <?php endif; ?>
        </tr>
        <?php
    }

    /**
     * Cleans a submitted value with the same rules as the settings pages.
     * Unslash $_POST data before passing it (wp_unslash()).
     *
     * @param mixed $input    Submitted value; null when the input was not sent (e.g. nothing selected).
     * @param mixed $previous Current saved value; a password keeps it when the input is empty.
     * @return mixed
     * @throws InvalidFieldValueException With a message for the user when the value is not valid.
     */
    public function sanitize(mixed $input, mixed $previous = null): mixed
    {
        return $this->renderer->sanitize($input, $this->field, $this->cast($previous));
    }
}