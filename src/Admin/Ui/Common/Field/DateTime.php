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
 * Class DateTime
 *
 * Renders HTML5 date, time, and datetime inputs.
 */
class DateTime extends AbstractField
{
    /**
     * Renders temporal input HTML markup.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'regular-text');
        $viewMode = (string)($fieldData['view_mode'] ?? 'datetime');
        $value = trim((string)$attr['value']);

        if ($viewMode === 'datetime' && $value !== '') {
            $value = str_replace(' ', 'T', $value);
            if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value)) {
                $value = substr($value, 0, 16);
            }
        }

        $inputType = match ($viewMode) {
            'time' => 'time',
            'date' => 'date',
            default => 'datetime-local',
        };

        ?>
        <input type="<?php echo esc_attr($inputType); ?>"
               id="<?php echo esc_attr($attr['id']); ?>"
               name="<?php echo esc_attr($attr['option_name']); ?>"
               class="<?php echo esc_attr($attr['classes']); ?>"
               value="<?php echo esc_attr($value); ?>" />

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }

    /**
     * Normalizes temporal values back to core storage standards.
     *
     * @param mixed $value
     * @param array<string, mixed> $fieldData
     * @return string
     */
    public function sanitize(mixed $value, array $fieldData): string
    {
        $clean = trim((string)$value);
        if ($clean === '') {
            return '';
        }

        $viewMode = (string)($fieldData['view_mode'] ?? 'datetime');

        if ($viewMode === 'datetime') {
            $clean = str_replace('T', ' ', $clean);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $clean)) {
                $clean .= ':00';
            }
        }

        if ($viewMode === 'time' && preg_match('/^\d{2}:\d{2}$/', $clean)) {
            $clean .= ':00';
        }

        return $clean;
    }
}