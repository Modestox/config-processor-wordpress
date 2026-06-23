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
 * Base class for all administrative layout input fields.
 */
abstract class AbstractField
{
    /**
     * Renders the input component HTML markup.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    abstract public function render(string $fieldKey, array $fieldData): void;

    /**
     * Sanitizes incoming request value data.
     *
     * @param mixed $value
     * @param array<string, mixed> $fieldData
     * @return mixed
     */
    public function sanitize(mixed $value, array $fieldData): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Computes shared UI parameters and metadata traits for input tags.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @param string $baseClass
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
        $optionName = array_key_exists('_forced_name', $fieldData)
            ? (string)$fieldData['_forced_name']
            : (string)($fieldData['_option_name'] ?? ('mtx_' . $fieldKey));

        $defaultValue = $fieldData['default'] ?? '';

        $customClass = (string)($fieldData['class'] ?? '');
        $inputClasses = $baseClass;
        if ($customClass !== '') {
            $inputClasses .= ' ' . $customClass;
        }

        if (array_key_exists('_forced_value', $fieldData)) {
            $rawValue = $fieldData['_forced_value'];
        } else {
            $rawValue = get_option($optionName, $defaultValue);
        }

        $idAttr = array_key_exists('_forced_name', $fieldData)
            ? 'crud_' . $optionName
            : 'config_' . $optionName;

        return [
            'option_name' => $optionName,
            'value'       => $rawValue,
            'comment'     => (string)($fieldData['comment'] ?? ''),
            'placeholder' => (string)($fieldData['placeholder'] ?? ''),
            'classes'     => $inputClasses,
            'id'          => $idAttr,
        ];
    }
}