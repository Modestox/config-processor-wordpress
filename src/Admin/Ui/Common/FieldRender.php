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

use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Text;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Textarea;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Password;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\BooleanToggle;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\YesNo;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Number;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\DateTime;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Select;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\Radio;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\MultiSelect;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\CheckboxGroup;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\DynamicRows;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\ImageUpload;
use Modestox\ConfigProcessorWp\Admin\Ui\Common\Field\InfoBlock;

/**
 * Class FieldRender
 *
 * Renders form fields using specific layout strategies.
 */
class FieldRender
{
    /**
     * Internal registry mapping field types to their designated renderer objects.
     *
     * @var array<string, object>
     */
    private array $renderers = [];

    /**
     * FieldRender constructor.
     *
     * @param array<string, array<string, mixed>> $fields Raw or schema-processed field configurations.
     * @param string $mode Layout presentation mode ('table' or 'div').
     */
    public function __construct(
            private readonly array $fields,
            private readonly string $mode = 'table',
    ) {
        $this->renderers = [
                'text'         => new Text(),
                'textarea'     => new Textarea(),
                'password'     => new Password(),
                'boolean'      => new BooleanToggle(),
                'yes_no'       => new YesNo(),
                'number'       => new Number(),
                'datetime'     => new DateTime(),
                'select'       => new Select(),
                'radio'        => new Radio(),
                'multiselect'  => new MultiSelect(),
                'checkbox'     => new CheckboxGroup(),
                'dynamic_rows' => new DynamicRows(),
                'image'        => new ImageUpload(),
                'infoblock'    => new InfoBlock(),
        ];
    }

    /**
     * Iterates through the field collection and routes rendering to the appropriate layout row method.
     *
     * @return void
     */
    public function render(): void
    {
        foreach ($this->fields as $key => $data) {
            if ($this->mode === 'table') {
                $this->renderTableRow((string)$key, (array)$data);
            } else {
                $this->renderDivRow((string)$key, (array)$data);
            }
        }
    }

    /**
     * Outputs a single configuration field wrapped inside a standard WordPress administrative table row framework.
     *
     * @param string $key Unique identifier or structural key of the field.
     * @param array<string, mixed> $data Configuration metadata including labels and types.
     * @return void
     */
    private function renderTableRow(string $key, array $data): void
    {
        $label = (string)($data['label'] ?? $key);
        $optionName = (string)($data['_option_name'] ?? ('mtx_sys_config_' . $key));
        $uniqueId = 'config_' . $optionName;
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($uniqueId); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <?php $this->renderFieldContent($key, $data); ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Outputs a single configuration field wrapped inside a modern flexible div container element block.
     *
     * @param string $key Unique identifier or structural key of the field.
     * @param array<string, mixed> $data Configuration metadata including labels and types.
     * @return void
     */
    private function renderDivRow(string $key, array $data): void
    {
        $label = (string)($data['label'] ?? $key);
        $optionName = (string)($data['_option_name'] ?? ('mtx_sys_config_' . $key));
        $uniqueId = 'config_' . $optionName;
        ?>
        <div class="mtx-sys-config-form-row">
            <label for="<?php echo esc_attr($uniqueId); ?>"><?php echo esc_html($label); ?></label>
            <?php $this->renderFieldContent($key, $data); ?>
        </div>
        <?php
    }

    /**
     * Extracts the target field type and delegates input markup construction to the registered type renderer class.
     *
     * @param string $key Unique identifier or structural key of the field.
     * @param array<string, mixed> $data Configuration metadata including labels and types.
     * @return void
     */
    private function renderFieldContent(string $key, array $data): void
    {
        $type = (string)($data['type'] ?? 'text');
        if (isset($this->renderers[$type])) {
            $this->renderers[$type]->render($key, $data);
        }
    }
}