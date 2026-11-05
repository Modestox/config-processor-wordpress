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
 * Class YesNo
 *
 * Yes/No select. The library fixes the options to 0/1, so the value is the integer 1 or 0.
 */
class YesNo extends Select
{
    /**
     * @inheritDoc
     */
    public function settingType(): string
    {
        return 'integer';
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(array $field): mixed
    {
        return (int)($field['default'] ?? 0);
    }

    /**
     * @inheritDoc
     */
    protected function normalize(mixed $value, array $field): int|string
    {
        return (int)parent::normalize($value, $field + ['options' => [0 => 'No', 1 => 'Yes']]);
    }

    /**
     * Translated labels, "Yes" first.
     *
     * @inheritDoc
     */
    protected function options(array $field): array
    {
        return [
                1 => __('Yes', 'modestox-config-processor-wp'),
                0 => __('No', 'modestox-config-processor-wp'),
        ];
    }
}