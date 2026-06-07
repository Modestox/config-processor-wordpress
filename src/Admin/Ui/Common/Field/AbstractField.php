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
 * Class AbstractField
 *
 * Provides shared computational logic and state preparation for administrative input elements.
 */
abstract class AbstractField
{
    /**
     * Renders the specific input component layout.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    abstract public function render(string $fieldKey, array $fieldData): void;

    /**
     * Sanitizes the incoming raw $_POST value based on field type specifications.
     *
     * @param mixed $value Raw input from the $_POST stream.
     * @param array<string, mixed> $fieldData Field configuration metadata context.
     * @return mixed
     */
    public function sanitize(mixed $value, array $fieldData): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Extracts and computes common field attributes from the raw schema metadata package.
     *
     * @param string               $fieldKey
     * @param array<string, mixed> $fieldData
     * @param string               $baseClass Default WordPress CSS class for the input type.
     * @return array{
     * option_name: string,
     * value: mixed,
     * comment: string,
     * placeholder: string,
     * classes: string,
     * id: string
     * }
     */
    protected function prepareAttributes(string $fieldKey, array $fieldData, string $baseClass = 'regular-text'): array
    {
        $optionName = (string)($fieldData['_option_name'] ?? ('mtx_sys_config_' . $fieldKey));
        $defaultValue = $fieldData['default'] ?? '';

        $customClass = (string)($fieldData['class'] ?? '');
        $inputClasses = $baseClass;
        if ($customClass !== '') {
            $inputClasses .= ' ' . $customClass;
        }

        $rawValue = get_option($optionName, $defaultValue);

        return [
            'option_name' => $optionName,
            'value'       => $rawValue,
            'comment'     => (string)($fieldData['comment'] ?? ''),
            'placeholder' => (string)($fieldData['placeholder'] ?? ''),
            'classes'     => $inputClasses,
            'id'          => 'config_' . $optionName,
        ];
    }
}