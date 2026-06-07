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
 * Class DynamicRows
 *
 * Generates expandable database serialization matrix arrays grids layout rows.
 */
class DynamicRows extends AbstractField
{
    /**
     * Renders custom procedural key-value schema matrix inputs with row action controls.
     *
     * @param string $fieldKey
     * @param array<string, mixed> $fieldData
     * @return void
     */
    public function render(string $fieldKey, array $fieldData): void
    {
        $attr = $this->prepareAttributes($fieldKey, $fieldData, 'mtx-dynamic-rows-table');
        $columns = (array)($fieldData['columns'] ?? []);
        $rows = is_array($attr['value']) ? $attr['value'] : (array)maybe_unserialize($attr['value']);
        ?>
        <div class="mtx-dynamic-rows-container" style="max-width: 650px;">
            <table id="<?php echo esc_attr($attr['id']); ?>" class="widefat striped <?php echo esc_attr($attr['classes']); ?>" style="margin-top:10px;">
                <thead>
                <tr>
                    <?php foreach ($columns as $colLabel): ?>
                        <th><?php echo esc_html(trim((string)$colLabel)); ?></th>
                    <?php endforeach; ?>
                    <th style="width: 40px; text-align: center;"><?php esc_html_e('Actions'); ?></th>
                </tr>
                </thead>
                <tbody class="mtx-dynamic-rows-body">
                <?php if (!empty($rows)): foreach ($rows as $index => $rowValues): ?>
                    <tr class="mtx-dynamic-row">
                        <?php foreach (array_keys($columns) as $colKey): ?>
                            <td>
                                <input type="text"
                                       name="<?php echo esc_attr($attr['option_name']); ?>[<?php echo (int)$index; ?>][<?php echo esc_attr($colKey); ?>]"
                                       value="<?php echo esc_attr((string)($rowValues[$colKey] ?? '')); ?>"
                                       class="regular-text" style="width:100%;" />
                            </td>
                        <?php endforeach; ?>
                        <td style="text-align: center; vertical-align: middle;">
                            <button type="button" class="button mtx-remove-row-btn" title="<?php esc_attr_e('Remove row'); ?>" style="color: #b32d2e; border-color: #b32d2e;">
                                <span class="dashicons dashicons-no-alt" style="vertical-align: middle; margin-top: -2px;"></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr class="mtx-dynamic-row">
                        <?php foreach (array_keys($columns) as $colKey): ?>
                            <td>
                                <input type="text"
                                       name="<?php echo esc_attr($attr['option_name']); ?>[0][<?php echo esc_attr($colKey); ?>]"
                                       value="" class="regular-text" style="width:100%;" />
                            </td>
                        <?php endforeach; ?>
                        <td style="text-align: center; vertical-align: middle;">
                            <button type="button" class="button mtx-remove-row-btn" title="<?php esc_attr_e('Remove row'); ?>" style="color: #b32d2e; border-color: #b32d2e;">
                                <span class="dashicons dashicons-no-alt" style="vertical-align: middle; margin-top: -2px;"></span>
                            </button>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 10px; text-align: right;">
                <button type="button" class="button button-secondary mtx-add-row-btn" data-table="<?php echo esc_attr($attr['id']); ?>">
                    <span class="dashicons dashicons-plus" style="vertical-align: middle; margin-top: -2px;"></span>
                    <?php esc_html_e('Add Row'); ?>
                </button>
            </div>

            <script type="text/template" id="<?php echo esc_attr($attr['id']); ?>-template">
                <tr class="mtx-dynamic-row">
                    <?php foreach (array_keys($columns) as $colKey): ?>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($attr['option_name']); ?>[{{index}}][<?php echo esc_attr($colKey); ?>]"
                                   value="" class="regular-text" style="width:100%;" />
                        </td>
                    <?php endforeach; ?>
                    <td style="text-align: center; vertical-align: middle;">
                        <button type="button" class="button mtx-remove-row-btn" title="<?php esc_attr_e('Remove row'); ?>" style="color: #b32d2e; border-color: #b32d2e;">
                            <span class="dashicons dashicons-no-alt" style="vertical-align: middle; margin-top: -2px;"></span>
                        </button>
                    </td>
                </tr>
            </script>
        </div>

        <?php if ($attr['comment'] !== ''): ?>
        <p class="mtx-sys-config-field-comment"><?php echo esc_html($attr['comment']); ?></p>
    <?php endif; ?>
        <?php
    }
}