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
 * Directs layout generation mapping field types to specific input renders.
 */
class FieldRender
{
    /**
     * @var array<string, object>
     */
    private array $renderers = [];

    /**
     * FieldRender constructor.
     *
     * @param array<string, array<string, mixed>> $fields
     * @param string $mode
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
     * Iterates through the field collection to execute row layout maps.
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
     * Outputs a field row wrapped in a standard WordPress table layout.
     *
     * @param string $key
     * @param array<string, mixed> $data
     * @return void
     */
    private function renderTableRow(string $key, array $data): void
    {
        $label = (string)($data['label'] ?? $key);
        $optionName = (string)($data['_option_name'] ?? ('mtx_sys_config_' . $key));
        $uniqueId = 'config_' . $optionName;

        $dependsData = isset($data['depends']) ? json_encode($data['depends'], JSON_UNESCAPED_SLASHES) : '';
        $styleAttr = '';

        if ($dependsData !== '') {
            $isVisible = $this->checkDependencies((array)$data['depends'], $optionName, $key);
            if (!$isVisible) {
                $styleAttr = ' style="display: none;"';
            }
        }

        $rowAttrs = $dependsData !== '' ? ' class="mtx-dependent-row" data-depends="' . esc_attr($dependsData) . '"' . $styleAttr : '';
        ?>
        <tr<?php echo $rowAttrs; ?>>
            <th scope="row"><label for="<?php echo esc_attr($uniqueId); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <?php $this->renderFieldContent($key, $data); ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Outputs a field row wrapped in a custom div layout.
     *
     * @param string $key
     * @param array<string, mixed> $data
     * @return void
     */
    private function renderDivRow(string $key, array $data): void
    {
        $label = (string)($data['label'] ?? $key);
        $optionName = (string)($data['_option_name'] ?? ('mtx_sys_config_' . $key));
        $uniqueId = 'config_' . $optionName;

        $dependsData = isset($data['depends']) ? json_encode($data['depends'], JSON_UNESCAPED_SLASHES) : '';
        $styleAttr = '';

        if ($dependsData !== '') {
            $isVisible = $this->checkDependencies((array)$data['depends'], $optionName, $key);
            if (!$isVisible) {
                $styleAttr = ' style="display: none;"';
            }
        }

        $rowAttrs = $dependsData !== '' ? ' class="mtx-sys-config-form-row mtx-dependent-row" data-depends="' . esc_attr(
                        $dependsData,
                ) . '"' . $styleAttr : ' class="mtx-sys-config-form-row"';
        ?>
        <div<?php echo $rowAttrs; ?>>
            <label for="<?php echo esc_attr($uniqueId); ?>"><?php echo esc_html($label); ?></label>
            <?php $this->renderFieldContent($key, $data); ?>
        </div>
        <?php
    }

    /**
     * Routes content generation to the matching field renderer instance.
     *
     * @param string $key
     * @param array<string, mixed> $data
     * @return void
     */
    private function renderFieldContent(string $key, array $data): void
    {
        $type = (string)($data['type'] ?? 'text');
        if (isset($this->renderers[$type])) {
            $this->renderers[$type]->render($key, $data);
        }
    }

    /**
     * Verifies if master dependency conditions are met during initial render.
     *
     * @param array<string, mixed> $dependencies
     * @param string $currentOptionName
     * @param string $currentFieldKey
     * @return bool
     */
    private function checkDependencies(array $dependencies, string $currentOptionName, string $currentFieldKey): bool
    {
        foreach ($dependencies as $masterKey => $expectedValue) {
            $masterOptionName = str_replace('_' . $currentFieldKey, '_' . $masterKey, $currentOptionName);
            $currentMasterValue = get_option($masterOptionName, '0');

            if (is_bool($currentMasterValue)) {
                $currentMasterValue = $currentMasterValue ? '1' : '0';
            }

            if ((string)$currentMasterValue !== (string)$expectedValue) {
                return false;
            }
        }

        return true;
    }
}