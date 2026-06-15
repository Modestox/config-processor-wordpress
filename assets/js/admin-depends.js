document.addEventListener('DOMContentLoaded', function () {
    /**
     * Initialization of field conditional visibilities (Depends Engine)
     */
    const dependentRows = document.querySelectorAll('.mtx-dependent-row');

    if (dependentRows.length > 0) {
        const masterRegistry = {};

        dependentRows.forEach(row => {
            const dependsJson = row.getAttribute('data-depends');
            if (!dependsJson) return;

            try {
                const dependencies = JSON.parse(dependsJson);
                const form = row.closest('form') || document.body;

                Object.keys(dependencies).forEach(masterFieldKey => {
                    // Universal selector search: looks for exact name match, naming prefixes, or array notations
                    const masterSelector = `[name="${masterFieldKey}"], [name$="_${masterFieldKey}"], [name$="[${masterFieldKey}]"], [name$="[${masterFieldKey}][]"]`;
                    const masterInputs = form.querySelectorAll(masterSelector);

                    masterInputs.forEach(masterInput => {
                        const masterName = masterInput.getAttribute('name');

                        if (!masterRegistry[masterName]) {
                            masterRegistry[masterName] = [];

                            masterInput.addEventListener('change', () => {
                                evaluateFormDependencies(form, masterRegistry);
                            });
                        }

                        masterRegistry[masterName].push({
                            row: row,
                            masterKey: masterFieldKey,
                            expectedValue: dependencies[masterFieldKey]
                        });
                    });
                });
            } catch (e) {
                console.error('Modestox Error parsing dependencies JSON:', e);
            }
        });

        // Initial run to apply the correct visibility states upon DOM page load
        const forms = document.querySelectorAll('form');
        if (forms.length > 0) {
            forms.forEach(form => evaluateFormDependencies(form, masterRegistry));
        } else {
            evaluateFormDependencies(document.body, masterRegistry);
        }
    }

    /**
     * Verifies and recalculates the visibility state of dependent container rows
     */
    function evaluateFormDependencies(container, registry) {
        Object.keys(registry).forEach(masterName => {
            const dependents = registry[masterName];
            const currentValue = getInputValue(container, masterName);

            dependents.forEach(dep => {
                if (String(currentValue) === String(dep.expectedValue)) {
                    dep.row.style.display = '';
                } else {
                    dep.row.style.display = 'none';
                }
            });
        });
    }

    /**
     * Helper method to extract the control input state value based on HTML types
     */
    function getInputValue(container, name) {
        const inputs = container.querySelectorAll(`[name="${name}"]`);
        if (inputs.length === 0) return '';

        if (inputs[0].type === 'radio' || inputs[0].type === 'checkbox') {
            const checkedInput = container.querySelector(`[name="${name}"]:checked`);
            return checkedInput ? checkedInput.value : '0';
        }

        return inputs[0].value;
    }

    /**
     * Dynamic Matrix Rows Layout Controls Engine (Dynamic Rows)
     */
    const addRowButtons = document.querySelectorAll('.mtx-add-row-btn');
    addRowButtons.forEach(button => {
        button.addEventListener('click', function () {
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector('.mtx-dynamic-rows-body');
            const templateHtml = document.getElementById(`${tableId}-template`).innerHTML;

            const index = tbody.querySelectorAll('.mtx-dynamic-row').length;
            const newRowHtml = templateHtml.replace(/\{\{index\}\}/g, index);

            tbody.insertAdjacentHTML('beforeend', newRowHtml);
        });
    });

    document.body.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.mtx-remove-row-btn');
        if (removeBtn) {
            const row = removeBtn.closest('.mtx-dynamic-row');
            if (row) {
                const tbody = row.parentNode;
                if (tbody.querySelectorAll('.mtx-dynamic-row').length > 1) {
                    row.remove();
                } else {
                    row.querySelectorAll('input').forEach(input => input.value = '');
                }
            }
        }
    });
});