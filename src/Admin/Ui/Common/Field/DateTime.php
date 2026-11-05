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

use DateTimeImmutable;

/**
 * Class DateTime
 *
 * Date, time or date and time, depending on 'view_mode'. Stored in the library formats:
 * 'Y-m-d H:i:s', 'Y-m-d' or 'H:i:s'. An empty string means "not set".
 */
class DateTime extends AbstractField
{
    /**
     * view_mode => [storage format, accepted input formats, HTML input type, HTML value format].
     */
    private const MODES = [
        'datetime' => ['Y-m-d H:i:s', ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'], 'datetime-local', 'Y-m-d\TH:i'],
        'date'     => ['Y-m-d', ['Y-m-d'], 'date', 'Y-m-d'],
        'time'     => ['H:i:s', ['H:i:s', 'H:i'], 'time', 'H:i'],
    ];

    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        [$storageFormat, , $inputType, $inputFormat] = $this->mode($field);

        $inputValue = '';
        if (is_string($value) && $value !== '') {
            $date = DateTimeImmutable::createFromFormat('!' . $storageFormat, $value);
            $inputValue = $date !== false ? $date->format($inputFormat) : '';
        }
        ?>
        <input type="<?php echo esc_attr($inputType); ?>"
               id="<?php echo esc_attr($this->inputId($optionName)); ?>"
               name="<?php echo esc_attr($optionName); ?>"
               class="<?php echo esc_attr($this->cssClass($field, 'regular-text')); ?>"
               value="<?php echo esc_attr($inputValue); ?>"
               <?php $this->requiredAttribute($field); ?> />
        <?php
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    protected function normalize(mixed $value, array $field): string
    {
        $value = trim($this->scalarToString($value));
        if ($value === '') {
            return '';
        }

        [$storageFormat, $accepted] = $this->mode($field);

        foreach ($accepted as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format($storageFormat);
            }
        }

        /* translators: %s: expected format, e.g. Y-m-d */
        $this->fail(sprintf(__('The value must be a valid date/time in the format %s.', 'modestox-config-processor-wp'), $storageFormat));
    }

    /**
     * @param array<string, mixed> $field
     * @return array{0: string, 1: list<string>, 2: string, 3: string}
     */
    private function mode(array $field): array
    {
        return self::MODES[(string)($field['view_mode'] ?? 'datetime')] ?? self::MODES['datetime'];
    }
}