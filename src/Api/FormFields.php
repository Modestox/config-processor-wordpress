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

use InvalidArgumentException;
use Modestox\ConfigProcessor\Exception\InvalidConfigException;
use Modestox\ConfigProcessorWp\Admin\Assets;
use Modestox\ConfigProcessorWp\Field\FieldTypeRegistry;

/**
 * Class FormFields
 *
 * Public entry point for rendering fields in other plugins' own forms. Get it with modestox_fields().
 *
 * The definition is validated by Modestox Config Processor exactly like a field of a settings page
 * (same keys, same strict types, custom 'x_' keys and custom field types included). 'depends' is not
 * available here: a single field has no group to depend on.
 *
 *     $ui = modestox_fields();
 *     $field = $ui->field('dynamic_rows', 'links', ['label' => 'Links', 'columns' => ['title' => 'Title', 'url' => 'URL']]);
 *     $field->renderRow($values['links'] ?? null);
 */
final class FormFields
{
    /**
     * @var array<string, FieldView>
     */
    private array $cache = [];

    public function __construct(
        private readonly FieldTypeRegistry $types,
        private readonly Assets $assets,
    ) {}

    /**
     * Validates a definition and returns the field.
     *
     * @param string               $type       Field type: 'text', 'select', 'dynamic_rows', ... or a custom type.
     * @param string               $name       HTML name of the input, e.g. 'title' or 'note[title]'.
     * @param array<string, mixed> $definition The same keys as in a settings schema, without 'type'.
     * @return FieldView
     * @throws InvalidConfigException   When the definition is not valid (message names the field).
     * @throws InvalidArgumentException When the name is empty.
     */
    public function field(string $type, string $name, array $definition = []): FieldView
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('The field name cannot be empty.');
        }

        $cacheKey = md5(serialize([$type, $name, $definition]));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        if (isset($definition['type']) && $definition['type'] !== $type) {
            throw new InvalidConfigException(sprintf("Field '%s': the 'type' key does not match the type argument '%s'.", $name, $type));
        }

        $key = self::keyFromName($name);

        try {
            $clean = $this->types->fieldsValidator()->validate([$key => ['type' => $type] + $definition]);
            $field = $clean[$key];
            $renderer = $this->types->renderer($type);
            $renderer->validateDefinition($field);
        } catch (InvalidConfigException $e) {
            throw new InvalidConfigException(sprintf("Field '%s': %s", $name, $e->getReason()), 0, $e);
        }

        return $this->cache[$cacheKey] = new FieldView($renderer, $name, $field, $this->assets);
    }

    /**
     * Forgiving shortcut: prints the field, or an inline error for administrators when the definition is invalid.
     *
     * @param string               $type
     * @param string               $name
     * @param array<string, mixed> $definition
     * @param mixed                $value      Current value; null shows the default.
     * @return bool                            False when the field could not be rendered.
     */
    public function render(string $type, string $name, array $definition = [], mixed $value = null): bool
    {
        try {
            $this->field($type, $name, $definition)->render($value);
            return true;
        } catch (InvalidConfigException|InvalidArgumentException $e) {
            self::printError($e->getMessage());
            return false;
        }
    }

    /**
     * Loads the field styles and scripts. Optional: FieldView::render() does it on first use,
     * calling it from 'admin_enqueue_scripts' avoids a late style.
     *
     * @return void
     */
    public function enqueueAssets(): void
    {
        $this->assets->enqueue();
    }

    /**
     * Prints a configuration error for administrators only.
     *
     * @param string $message
     * @return void
     */
    public static function printError(string $message): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        printf('<div class="notice notice-error inline"><p>%s</p></div>', esc_html('Modestox field error: ' . $message));
    }

    /**
     * Identifier for the library validator: 'note[title]' -> 'note_title'.
     *
     * @param string $name
     * @return string
     */
    private static function keyFromName(string $name): string
    {
        $key = trim((string)preg_replace('/[^a-z0-9_]+/', '_', strtolower($name)), '_');

        return $key !== '' ? $key : 'field';
    }
}