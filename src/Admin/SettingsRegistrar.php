<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Admin;

use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\AbstractField;
use Modestox\ConfigProcessorWp\Exception\InvalidFieldValueException;
use Modestox\ConfigProcessorWp\Registry\ConfigRegistry;
use Modestox\ConfigProcessorWp\Registry\OptionDefinition;

/**
 * Class SettingsRegistrar
 *
 * Registers every schema field as a WordPress setting with its default and a sanitize callback.
 */
final class SettingsRegistrar
{
    /**
     * Options already reported in this request. WordPress may sanitize the same value twice
     * (update_option() falls through to add_option() for a new option).
     *
     * @var array<string, true>
     */
    private array $reported = [];

    public function __construct(private readonly ConfigRegistry $registry) {}

    /**
     * @return void
     */
    public function register(): void
    {
        $types = $this->registry->types();

        foreach ($this->registry->options() as $name => $option) {
            $renderer = $types->renderer((string)$option->field['type']);

            register_setting($option->group, $name, [
                'type'              => $renderer->settingType(),
                'default'           => $renderer->defaultValue($option->field),
                'sanitize_callback' => fn(mixed $value): mixed => $this->sanitize($option, $renderer, $value),
                'show_in_rest'      => false,
            ]);
        }

        foreach ($this->registry->pages() as $page) {
            $capability = $page->capability;
            foreach ($page->optionGroups() as $group) {
                add_filter("option_page_capability_{$group}", static fn(): string => $capability);
            }
        }
    }

    /**
     * An invalid value is reported on the settings page and the previous value is kept.
     *
     * @param OptionDefinition $option
     * @param AbstractField    $renderer
     * @param mixed            $value
     * @return mixed
     */
    private function sanitize(OptionDefinition $option, AbstractField $renderer, mixed $value): mixed
    {
        $previous = $renderer->cast(get_option($option->name, $renderer->defaultValue($option->field)), $option->field);

        try {
            return $renderer->sanitize($value, $option->field, $previous);
        } catch (InvalidFieldValueException $e) {
            if (!isset($this->reported[$option->name])) {
                $this->reported[$option->name] = true;
                add_settings_error(
                    $option->name,
                    'mtx_invalid_value',
                    sprintf('%s: %s', (string)($option->field['label'] ?? $option->name), $e->getMessage()),
                );
            }

            return $previous;
        }
    }
}