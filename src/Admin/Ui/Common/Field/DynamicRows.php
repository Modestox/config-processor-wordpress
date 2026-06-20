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
 * Renders expandable key-value matrix grids.
 */
class DynamicRows extends AbstractField
{
    /**
     * Renders procedural repeatable table layout rows.
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
        <div class="mtx-dynamic-rows-container">
            <table id="<?php echo esc_attr($attr['id']); ?>" class="widefat striped <?php echo esc_attr($attr['classes']); ?>">
                <thead>
                <tr>
                    <?php foreach ($columns as $colLabel): ?>
                        <th><?php echo esc_html(trim((string)$colLabel)); ?></th>
                    <?php endforeach; ?>
                    <th class="mtx-actions-column"><?php echo esc_html__('Actions', 'modestox-config-processor-wp'); ?></th>
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
                                       class="regular-text"/>
                            </td>
                        <?php endforeach; ?>
                        <td class="mtx-action-cell">
                            <button type="button" class="button mtx-remove-row-btn"
                                    title="<?php echo esc_attr__('Remove row', 'modestox-config-processor-wp'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr class="mtx-dynamic-row">
                        <?php foreach (array_keys($columns) as $colKey): ?>
                            <td>
                                <input type="text"
                                       name="<?php echo esc_attr($attr['option_name']); ?>[0][<?php echo esc_attr($colKey); ?>]"
                                       value="" class="regular-text"/>
                            </td>
                        <?php endforeach; ?>
                        <td class="mtx-action-cell">
                            <button type="button" class="button mtx-remove-row-btn"
                                    title="<?php echo esc_attr__('Remove row', 'modestox-config-processor-wp'); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <div class="mtx-dynamic-rows-actions">
                <button type="button" class="button button-secondary mtx-add-row-btn" data-table="<?php echo esc_attr($attr['id']); ?>">
                    <span class="dashicons dashicons-plus"></span>
                    <?php echo esc_html__('Add Row', 'modestox-config-processor-wp'); ?>
                </button>
            </div>

            <script type="text/template" id="<?php echo esc_attr($attr['id']); ?>-template">
                <tr class="mtx-dynamic-row">
                    <?php foreach (array_keys($columns) as $colKey): ?>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($attr['option_name']); ?>[{{index}}][<?php echo esc_attr($colKey); ?>]"
                                   value="" class="regular-text"/>
                        </td>
                    <?php endforeach; ?>
                    <td class="mtx-action-cell">
                        <button type="button" class="button mtx-remove-row-btn"
                                title="<?php echo esc_attr__('Remove row', 'modestox-config-processor-wp'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
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