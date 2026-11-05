/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

/**
 * Field dependencies ('depends').
 *
 * Every dependent row carries data-depends with rules built on the server:
 *   [{"option": "<full option name of the parent>", "type": "<parent type>", "value": "<expected value>"}]
 * A row is visible when every parent row is visible and every parent has the expected value.
 * The rules mirror DependencyResolver.php.
 */
(() => {
    'use strict';

    /**
     * Current value of a field inside the form, by its option name.
     *
     * @param {HTMLFormElement} form
     * @param {string} name
     * @return {string|string[]|null}
     */
    const readValue = (form, name) => {
        // multiselect and checkbox group: name[]
        const multiple = form.querySelectorAll(`[name="${name}[]"]`);
        if (multiple.length > 0) {
            const values = [];
            multiple.forEach((element) => {
                if (element.tagName === 'SELECT') {
                    Array.from(element.selectedOptions).forEach((option) => values.push(option.value));
                } else if (element.checked) {
                    values.push(element.value);
                }
            });
            return values;
        }

        const inputs = Array.from(form.querySelectorAll(`[name="${name}"]`));
        if (inputs.length === 0) {
            return null;
        }

        // boolean: a hidden "0" followed by the checkbox
        const checkbox = inputs.find((element) => element.type === 'checkbox');
        if (checkbox) {
            return checkbox.checked ? checkbox.value : '0';
        }

        const radios = inputs.filter((element) => element.type === 'radio');
        if (radios.length > 0) {
            const checked = radios.find((element) => element.checked);
            return checked ? checked.value : '';
        }

        return inputs[0].value;
    };

    /**
     * @param {string|string[]|null} current
     * @param {{type: string, value: string}} rule
     * @return {boolean}
     */
    const matches = (current, rule) => {
        if (current === null) {
            return false;
        }
        if (Array.isArray(current)) {
            return current.includes(rule.value);
        }
        if (rule.type === 'number') {
            const a = parseFloat(current);
            const b = parseFloat(rule.value);
            if (!Number.isNaN(a) && !Number.isNaN(b)) {
                return a === b;
            }
        }
        return String(current) === rule.value;
    };

    /**
     * Hidden fields must not block the submit with "required".
     *
     * @param {HTMLElement} row
     * @param {boolean} visible
     */
    const toggleRequired = (row, visible) => {
        row.querySelectorAll('[required], [data-mtx-required]').forEach((element) => {
            if (visible && element.dataset.mtxRequired) {
                element.required = true;
                delete element.dataset.mtxRequired;
            } else if (!visible && element.required) {
                element.required = false;
                element.dataset.mtxRequired = '1';
            }
        });
    };

    /**
     * @param {HTMLFormElement} form
     */
    const evaluate = (form) => {
        const rowsByOption = new Map();
        form.querySelectorAll('[data-mtx-option]').forEach((row) => rowsByOption.set(row.dataset.mtxOption, row));

        const cache = new Map();

        const isVisible = (row, trail) => {
            if (cache.has(row)) {
                return cache.get(row);
            }

            let rules = [];
            try {
                rules = JSON.parse(row.dataset.depends || '[]');
            } catch (error) {
                console.error('Modestox: invalid data-depends JSON.', error);
            }

            let visible = true;
            for (const rule of rules) {
                const parentRow = rowsByOption.get(rule.option);
                if (parentRow && parentRow.classList.contains('mtx-dependent-row') && !trail.includes(parentRow)) {
                    if (!isVisible(parentRow, trail.concat(row))) {
                        visible = false;
                        break;
                    }
                }
                if (!matches(readValue(form, rule.option), rule)) {
                    visible = false;
                    break;
                }
            }

            cache.set(row, visible);
            return visible;
        };

        form.querySelectorAll('.mtx-dependent-row').forEach((row) => {
            const visible = isVisible(row, []);
            row.style.display = visible ? '' : 'none';
            toggleRequired(row, visible);
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form').forEach((form) => {
            if (!form.querySelector('.mtx-dependent-row')) {
                return;
            }
            evaluate(form);
            form.addEventListener('change', () => evaluate(form));
            form.addEventListener('input', () => evaluate(form));
        });
    });
})();