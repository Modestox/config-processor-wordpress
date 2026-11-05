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

use Modestox\ConfigProcessor\Exception\InvalidConfigException;

/**
 * Class DynamicRows
 *
 * Repeatable table rows with the text columns from 'columns'.
 * The value is a list of rows, each row has every column; fully empty rows are dropped.
 *
 * Locked default rows ('x_locked_column' => '<column id>'):
 *  - the rows from 'default' cannot be removed, their value in that column cannot be changed;
 *    the other cells of those rows stay editable;
 *  - the column becomes a required, unique key for every row;
 *  - new rows can be added and removed freely;
 *  - a missing default row is restored on save and on read, so code may rely on it;
 *  - default rows keep their places and their order from 'default' (also enforced on save).
 *
 * Rows that are not locked can be moved up and down; they swap only with a neighbouring free row,
 * so they never push a locked row to another position.
 */
class DynamicRows extends AbstractField
{
    public const LOCKED_COLUMN_KEY = 'x_locked_column';

    /**
     * @inheritDoc
     */
    public function render(string $optionName, array $field, mixed $value): void
    {
        $columns = array_map('strval', (array)($field['columns'] ?? []));
        $rows = is_array($value) && $value !== [] ? array_values($value) : [array_fill_keys(array_keys($columns), '')];
        $tableId = $this->inputId($optionName);
        $lockedColumn = $this->lockedColumn($field);
        $lockedKeys = $this->lockedKeys($field);
        ?>
        <div class="mtx-dynamic-rows-container">
            <table id="<?php echo esc_attr($tableId); ?>"
                   class="widefat striped <?php echo esc_attr($this->cssClass($field, 'mtx-dynamic-rows-table')); ?>">
                <thead>
                <tr>
                    <?php foreach ($columns as $columnLabel): ?>
                        <th><?php echo esc_html($columnLabel); ?></th>
                    <?php endforeach; ?>
                    <th class="mtx-actions-column"><?php echo esc_html__('Actions', 'modestox-config-processor-wp'); ?></th>
                </tr>
                </thead>
                <tbody class="mtx-dynamic-rows-body">
                <?php foreach ($rows as $index => $row):
                    $row = is_array($row) ? $row : [];
                    $isLocked = $lockedColumn !== null && isset($lockedKeys[(string)($row[$lockedColumn] ?? '')]);
                    $this->renderRow($optionName, $columns, (string)$index, $row, $isLocked ? $lockedColumn : null);
                endforeach; ?>
                </tbody>
            </table>
            <div class="mtx-dynamic-rows-actions">
                <button type="button" class="button button-secondary mtx-add-row-btn" data-table="<?php echo esc_attr($tableId); ?>">
                    <span class="dashicons dashicons-plus"></span>
                    <?php echo esc_html__('Add Row', 'modestox-config-processor-wp'); ?>
                </button>
            </div>
            <script type="text/template" id="<?php echo esc_attr($tableId); ?>-template">
                <?php $this->renderRow($optionName, $columns, '{{index}}', [], null); ?>
            </script>
        </div>
        <?php if ($lockedColumn !== null && $lockedKeys !== []): ?>
            <p class="description mtx-sys-config-field-comment">
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: column label */
                    __('Default rows cannot be removed and their "%s" cannot be changed. Every row needs a unique "%s".', 'modestox-config-processor-wp'),
                    $columns[$lockedColumn] ?? $lockedColumn,
                    $columns[$lockedColumn] ?? $lockedColumn,
                ));
                ?>
            </p>
        <?php endif;
        $this->renderComment($field);
    }

    /**
     * @inheritDoc
     */
    public function validateDefinition(array $field): void
    {
        if (!array_key_exists(self::LOCKED_COLUMN_KEY, $field)) {
            return;
        }

        $columns = (array)($field['columns'] ?? []);
        $column = $field[self::LOCKED_COLUMN_KEY];

        if (!is_string($column) || !array_key_exists($column, $columns)) {
            throw new InvalidConfigException(sprintf(
                "Field '%s' must name one of the columns (%s).",
                self::LOCKED_COLUMN_KEY,
                implode(', ', array_map('strval', array_keys($columns))),
            ));
        }

        $seen = [];
        foreach ((array)($field['default'] ?? []) as $index => $row) {
            $key = trim((string)($row[$column] ?? ''));
            if ($key === '') {
                throw new InvalidConfigException(
                    sprintf("Default row '%s' has no value in the locked column '%s'.", (string)$index, $column),
                );
            }
            if (isset($seen[$key])) {
                throw new InvalidConfigException(
                    sprintf("The value '%s' of the locked column '%s' is used by more than one default row.", $key, $column),
                );
            }
            $seen[$key] = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function settingType(): string
    {
        return 'array';
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(array $field): mixed
    {
        return (array)($field['default'] ?? []);
    }

    /**
     * @inheritDoc
     */
    public function hasLabelTarget(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function sanitize(mixed $input, array $field, mixed $previous): mixed
    {
        $rows = $this->normalize($input, $field);
        $lockedColumn = $this->lockedColumn($field);

        foreach ($rows as $index => $row) {
            foreach ($row as $column => $cell) {
                // The key of a locked row is restored from the definition and stays as it is.
                if ($column === $lockedColumn) {
                    continue;
                }
                $rows[$index][$column] = $this->filterMarkup($cell);
            }
        }

        return $rows;
    }

    /**
     * @inheritDoc
     * @return list<array<string, string>>
     */
    protected function normalize(mixed $value, array $field): array
    {
        $rows = $this->normalizeRows($value, $field);

        $lockedColumn = $this->lockedColumn($field);
        if ($lockedColumn === null) {
            return $rows;
        }

        $columnLabel = (string)(($field['columns'] ?? [])[$lockedColumn] ?? $lockedColumn);
        $seen = [];
        foreach ($rows as $row) {
            $key = $row[$lockedColumn];
            if ($key === '') {
                /* translators: %s: column label */
                $this->fail(sprintf(__('Every row needs a value in the "%s" column.', 'modestox-config-processor-wp'), $columnLabel));
            }
            if (isset($seen[$key])) {
                /* translators: 1: value, 2: column label */
                $this->fail(sprintf(__('The value "%1$s" in the "%2$s" column is used more than once.', 'modestox-config-processor-wp'), $key, $columnLabel));
            }
            $seen[$key] = true;
        }

        // Put back every default row that is missing, at its default position.
        $lockedRows = $this->lockedRows($field);
        foreach ($lockedRows as $position => $lockedRow) {
            if (!isset($seen[$lockedRow[$lockedColumn]])) {
                array_splice($rows, min($position, count($rows)), 0, [$lockedRow]);
            }
        }

        // Default rows keep the order from 'default': the slots they occupy are refilled in that order.
        // Their edited cells (e.g. the title) are kept.
        $byKey = [];
        $slots = [];
        $lockedKeys = $this->lockedKeys($field);
        foreach ($rows as $index => $row) {
            if (isset($lockedKeys[$row[$lockedColumn]])) {
                $byKey[$row[$lockedColumn]] = $row;
                $slots[] = $index;
            }
        }
        foreach ($lockedRows as $order => $lockedRow) {
            $rows[$slots[$order]] = $byKey[$lockedRow[$lockedColumn]];
        }

        return $rows;
    }

    /**
     * @param mixed                $value
     * @param array<string, mixed> $field
     * @return list<array<string, string>>
     * @throws \Modestox\ConfigProcessorWp\Exception\InvalidFieldValueException
     */
    private function normalizeRows(mixed $value, array $field): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $this->fail(__('The value must be a list of rows.', 'modestox-config-processor-wp'));
        }

        $columns = (array)($field['columns'] ?? []);
        $rows = [];

        foreach ($value as $row) {
            if (!is_array($row)) {
                $this->fail(__('Every row must be a list of cells.', 'modestox-config-processor-wp'));
            }

            foreach (array_keys($row) as $column) {
                if (!array_key_exists((string)$column, $columns)) {
                    /* translators: %s: column identifier */
                    $this->fail(sprintf(__('Unknown column "%s".', 'modestox-config-processor-wp'), (string)$column));
                }
            }

            $clean = [];
            foreach (array_keys($columns) as $column) {
                $cell = str_replace(["\r\n", "\r", "\n", "\0"], [' ', ' ', ' ', ''], $this->scalarToString($row[$column] ?? ''));
                $clean[(string)$column] = trim($cell);
            }

            if (implode('', $clean) !== '') {
                $rows[] = $clean;
            }
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $field
     * @return string|null
     */
    private function lockedColumn(array $field): ?string
    {
        $column = $field[self::LOCKED_COLUMN_KEY] ?? null;

        return is_string($column) && array_key_exists($column, (array)($field['columns'] ?? [])) ? $column : null;
    }

    /**
     * Default rows with every column, keyed by nothing (list).
     *
     * @param array<string, mixed> $field
     * @return list<array<string, string>>
     */
    private function lockedRows(array $field): array
    {
        if ($this->lockedColumn($field) === null) {
            return [];
        }

        $rows = [];
        foreach ((array)($field['default'] ?? []) as $row) {
            $clean = [];
            foreach (array_keys((array)($field['columns'] ?? [])) as $column) {
                $clean[(string)$column] = trim((string)($row[$column] ?? ''));
            }
            $rows[] = $clean;
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, true>
     */
    private function lockedKeys(array $field): array
    {
        $column = $this->lockedColumn($field);
        $keys = [];
        foreach ($this->lockedRows($field) as $row) {
            $keys[$row[(string)$column]] = true;
        }

        return $keys;
    }

    /**
     * @param string                $optionName
     * @param array<string, string> $columns
     * @param string                $index
     * @param array<string, mixed>  $row
     * @param string|null           $lockedColumn Column that is read-only in this row; null for a free row.
     * @return void
     */
    private function renderRow(string $optionName, array $columns, string $index, array $row, ?string $lockedColumn): void
    {
        $isLocked = $lockedColumn !== null;
        ?>
        <tr class="mtx-dynamic-row<?php echo $isLocked ? ' mtx-locked-row' : ''; ?>">
            <?php foreach (array_keys($columns) as $column): ?>
                <td>
                    <input type="text"
                           name="<?php echo esc_attr(sprintf('%s[%s][%s]', $optionName, $index, $column)); ?>"
                           value="<?php echo esc_attr(is_scalar($row[$column] ?? null) ? (string)$row[$column] : ''); ?>"
                           class="regular-text"
                           <?php echo $isLocked && $column === $lockedColumn ? 'readonly="readonly"' : ''; ?> />
                </td>
            <?php endforeach; ?>
            <td class="mtx-action-cell">
                <?php if ($isLocked): ?>
                    <span class="dashicons dashicons-lock mtx-locked-icon"
                          title="<?php echo esc_attr__('Default row, it cannot be removed', 'modestox-config-processor-wp'); ?>"></span>
                <?php else: ?>
                    <span class="mtx-move-group">
                        <button type="button" class="button mtx-move-row-btn" data-direction="up"
                                title="<?php echo esc_attr__('Move up', 'modestox-config-processor-wp'); ?>"
                                aria-label="<?php echo esc_attr__('Move up', 'modestox-config-processor-wp'); ?>">
                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </button><button type="button" class="button mtx-move-row-btn" data-direction="down"
                                title="<?php echo esc_attr__('Move down', 'modestox-config-processor-wp'); ?>"
                                aria-label="<?php echo esc_attr__('Move down', 'modestox-config-processor-wp'); ?>">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                    </span>
                    <button type="button" class="button mtx-remove-row-btn"
                            title="<?php echo esc_attr__('Remove row', 'modestox-config-processor-wp'); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}