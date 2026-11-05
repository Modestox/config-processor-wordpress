<?php
/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

declare(strict_types=1);

namespace Modestox\ConfigProcessorWp\Field;

use LogicException;
use Modestox\ConfigProcessor\Schema\GroupedConfig;
use Modestox\ConfigProcessor\Schema\SystemConfig;
use Modestox\ConfigProcessor\Validator\SystemConfig\Fields\FieldValidatorInterface;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\AbstractField;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\BooleanToggle;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\CheckboxGroup;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\DateTime;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\DynamicRows;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\File;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\ImageUpload;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\InfoBlock;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\MultiSelect;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Number;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Password;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Radio;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Select;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Text;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Textarea;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\YesNo;

/**
 * Class FieldTypeRegistry
 *
 * Maps every field type to its renderer and collects validators of custom types.
 *
 * Custom types come from the 'modestox_register_field_types' filter:
 *
 *     $types['color'] = ['validator' => new ColorValidator(), 'renderer' => new ColorField()];
 *
 * A built-in type may be given only a 'renderer' to replace its markup.
 */
final class FieldTypeRegistry
{
    private const TYPE_PATTERN = '/^[a-z0-9_]+$/D';

    /**
     * @var array<string, class-string<AbstractField>>
     */
    private const BUILT_IN = [
        'text'         => Text::class,
        'textarea'     => Textarea::class,
        'password'     => Password::class,
        'boolean'      => BooleanToggle::class,
        'yes_no'       => YesNo::class,
        'number'       => Number::class,
        'datetime'     => DateTime::class,
        'select'       => Select::class,
        'radio'        => Radio::class,
        'multiselect'  => MultiSelect::class,
        'checkbox'     => CheckboxGroup::class,
        'dynamic_rows' => DynamicRows::class,
        'file'         => File::class,
        'image'        => ImageUpload::class,
        'infoblock'    => InfoBlock::class,
    ];

    /**
     * @var array<string, AbstractField>
     */
    private array $renderers = [];

    /**
     * @var array<string, FieldValidatorInterface>
     */
    private array $validators = [];

    public function __construct()
    {
        foreach (self::BUILT_IN as $type => $class) {
            $this->renderers[$type] = new $class();
        }
    }

    /**
     * Adds custom types. Invalid definitions are skipped.
     *
     * @param mixed $definitions Result of the 'modestox_register_field_types' filter.
     * @return list<string> Error messages.
     */
    public function extend(mixed $definitions): array
    {
        if (!is_array($definitions)) {
            return ["The 'modestox_register_field_types' filter must return an array."];
        }

        $errors = [];
        foreach ($definitions as $type => $definition) {
            if (!is_string($type) || !preg_match(self::TYPE_PATTERN, $type)) {
                $errors[] = sprintf("Field type '%s': the name may contain only a-z, 0-9 and '_'.", (string)$type);
                continue;
            }

            if (!is_array($definition)) {
                $errors[] = sprintf("Field type '%s': the definition must be an array with 'renderer' and 'validator'.", $type);
                continue;
            }

            $unknown = array_diff(array_keys($definition), ['renderer', 'validator']);
            if ($unknown !== []) {
                $errors[] = sprintf("Field type '%s': unknown keys %s. Allowed keys: renderer, validator.", $type, implode(', ', $unknown));
                continue;
            }

            $renderer = $definition['renderer'] ?? null;
            $validator = $definition['validator'] ?? null;

            if (!$renderer instanceof AbstractField) {
                $errors[] = sprintf("Field type '%s': 'renderer' must be an instance of %s.", $type, AbstractField::class);
                continue;
            }

            if ($validator !== null && !$validator instanceof FieldValidatorInterface) {
                $errors[] = sprintf("Field type '%s': 'validator' must implement %s.", $type, FieldValidatorInterface::class);
                continue;
            }

            if ($validator === null && !isset(self::BUILT_IN[$type])) {
                $errors[] = sprintf("Field type '%s' is not built in, so it needs a 'validator'.", $type);
                continue;
            }

            $this->renderers[$type] = $renderer;
            if ($validator !== null) {
                $this->validators[$type] = $validator;
            }
        }

        return $errors;
    }

    /**
     * @param string $type
     * @return AbstractField
     */
    public function renderer(string $type): AbstractField
    {
        return $this->renderers[$type] ?? throw new LogicException("No renderer is registered for field type '{$type}'.");
    }

    /**
     * @return SystemConfig
     */
    public function systemSchema(): SystemConfig
    {
        return $this->validators === [] ? new SystemConfig() : SystemConfig::withFieldTypes($this->validators);
    }

    /**
     * @return GroupedConfig
     */
    public function groupedSchema(): GroupedConfig
    {
        return $this->validators === [] ? new GroupedConfig() : GroupedConfig::withFieldTypes($this->validators);
    }
}