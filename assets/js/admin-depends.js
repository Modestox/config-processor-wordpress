/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

document.addEventListener('DOMContentLoaded', () => {
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

        const forms = document.querySelectorAll('form');
        if (forms.length > 0) {
            forms.forEach(form => evaluateFormDependencies(form, masterRegistry));
        } else {
            evaluateFormDependencies(document.body, masterRegistry);
        }
    }

    /**
     * Evaluates and updates the visibility of dependent fields layout rows.
     *
     * @param {HTMLElement} container
     * @param {Object} registry
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
     * Extracts active input field value state based on HTML types.
     *
     * @param {HTMLElement} container
     * @param {string} name
     * @return {string}
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
});