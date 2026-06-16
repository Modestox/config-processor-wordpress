/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

document.addEventListener('DOMContentLoaded', () => {
    /**
     * Toggles the visibility of remove buttons based on row count.
     * Prevents deleting the absolute last row.
     *
     * @param {HTMLElement} tableBody
     */
    const updateRemoveButtonsState = (tableBody) => {
        const rows = tableBody.querySelectorAll('.mtx-dynamic-row');
        rows.forEach(row => {
            const removeBtn = row.querySelector('.mtx-remove-row-btn');
            if (removeBtn) {
                removeBtn.disabled = rows.length <= 1;
            }
        });
    };

    // Global click delegation handler
    document.addEventListener('click', (event) => {
        const target = event.target;

        // 1. Add dynamic row logic
        const addBtn = target.closest('.mtx-add-row-btn');
        if (addBtn) {
            event.preventDefault();

            const tableId = addBtn.getAttribute('data-table');
            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector('.mtx-dynamic-rows-body');
            const templateHtml = document.getElementById(`${tableId}-template`)?.innerHTML;
            if (!tbody || !templateHtml) return;

            const currentIndex = tbody.querySelectorAll('.mtx-dynamic-row').length;
            const compiledHtml = templateHtml.replace(/\{\{index\}\}/g, currentIndex.toString());

            tbody.insertAdjacentHTML('beforeend', compiledHtml);
            updateRemoveButtonsState(tbody);
            return;
        }

        // 2. Remove dynamic row logic
        const removeBtn = target.closest('.mtx-remove-row-btn');
        if (removeBtn) {
            event.preventDefault();

            const row = removeBtn.closest('.mtx-dynamic-row');
            if (!row) return;

            const tbody = row.closest('.mtx-dynamic-rows-body');
            if (!tbody) return;

            if (tbody.querySelectorAll('.mtx-dynamic-row').length <= 1) return;

            row.remove();

            // Re-index remaining rows fields
            tbody.querySelectorAll('.mtx-dynamic-row').forEach((currentRow, newIndex) => {
                currentRow.querySelectorAll('input').forEach(input => {
                    const currentName = input.getAttribute('name');
                    if (currentName) {
                        const updatedName = currentName.replace(/\[\d+\]/, `[${newIndex}]`);
                        input.setAttribute('name', updatedName);
                    }
                });
            });

            updateRemoveButtonsState(tbody);
        }
    });

    // Initial state validation check for dynamic tables
    document.querySelectorAll('.mtx-dynamic-rows-body').forEach(tbody => {
        updateRemoveButtonsState(tbody);
    });

    // 3. WordPress Media Uploader Integration
    document.addEventListener('click', (event) => {
        const uploadBtn = event.target.closest('.mtx-media-upload-trigger');
        if (!uploadBtn) return;

        event.preventDefault();

        const targetInputId = uploadBtn.getAttribute('data-target');
        const targetInput = document.getElementById(targetInputId);
        if (!targetInput) return;

        const container = document.getElementById(`container_${targetInputId}`);
        const modalTitle = uploadBtn.getAttribute('data-label-upload') || 'Choose Image';

        const frame = wp.media({
            title: modalTitle,
            button: {
                text: uploadBtn.getAttribute('data-label-change') || 'Use this image'
            },
            multiple: false
        });

        frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();
            targetInput.value = attachment.url;

            // Extract localized site name passed from PHP via wp_localize_script
            const siteName = (typeof mtxConfigData !== 'undefined' && mtxConfigData.siteName)
                ? mtxConfigData.siteName
                : 'WordPress Site';

            if (container) {
                container.querySelectorAll('.mtx-preview-browser-tab span').forEach(span => {
                    span.textContent = siteName;
                });
            }

            targetInput.dispatchEvent(new Event('change', { bubbles: true }));
        });

        frame.open();
    });

    // 4. Media Preview UI State Toggle
    document.addEventListener('click', (event) => {
        const target = event.target;

        const removeMediaBtn = target.closest('.mtx-media-remove-trigger');
        if (removeMediaBtn) {
            event.preventDefault();

            const targetInputId = removeMediaBtn.getAttribute('data-target');
            const targetInput = document.getElementById(targetInputId);
            const container = document.getElementById(`container_${targetInputId}`);
            if (!targetInput || !container) return;

            targetInput.value = '';

            const previewBox = container.querySelector('.mtx-media-preview-box');
            if (previewBox) previewBox.style.display = 'none';

            const uploadBtn = container.querySelector('.mtx-media-upload-trigger');
            if (uploadBtn) {
                uploadBtn.textContent = uploadBtn.getAttribute('data-label-upload') || 'Upload';
            }

            removeMediaBtn.style.display = 'none';
            targetInput.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        const uploadMediaBtn = target.closest('.mtx-media-upload-trigger');
        if (uploadMediaBtn) {
            const targetInputId = uploadMediaBtn.getAttribute('data-target');
            const container = document.getElementById(`container_${targetInputId}`);
            if (!container) return;

            const targetInput = document.getElementById(targetInputId);
            if (!targetInput) return;

            const originalChangeHandler = () => {
                const newUrl = targetInput.value;
                if (newUrl === '') return;

                container.querySelectorAll('.mtx-preview-image-node, .mtx-preview-favicon-node').forEach(img => {
                    img.setAttribute('src', newUrl);
                });

                const previewBox = container.querySelector('.mtx-media-preview-box');
                if (previewBox) previewBox.style.display = '';

                uploadMediaBtn.textContent = uploadMediaBtn.getAttribute('data-label-change') || 'Change';

                const removeBtn = container.querySelector('.mtx-media-remove-trigger');
                if (removeBtn) removeBtn.style.display = '';

                targetInput.removeEventListener('change', originalChangeHandler);
            };

            targetInput.addEventListener('change', originalChangeHandler);
        }
    });
});