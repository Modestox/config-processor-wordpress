/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

document.addEventListener('DOMContentLoaded', () => {
    /**
     * Toggles the visibility of remove buttons based on the remaining rows count.
     * Prevents deleting the absolute last row to maintain form structural integrity.
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

    // Global delegation context handler for clicks within the administration page container
    document.addEventListener('click', (event) => {
        const target = event.target;

        // 1. Handle dynamic row addition process flow
        const addBtn = target.closest('.mtx-add-row-btn');
        if (addBtn) {
            event.preventDefault();

            const tableId = addBtn.getAttribute('data-table');
            const table = document.getElementById(tableId);
            if (!table) return;

            const tbody = table.querySelector('.mtx-dynamic-rows-body');
            const templateHtml = document.getElementById(`${tableId}-template`)?.innerHTML;
            if (!tbody || !templateHtml) return;

            // Calculate current explicit index based on the total number of child rows present
            const currentIndex = tbody.querySelectorAll('.mtx-dynamic-row').length;

            // Compile the virtual template block by substituting macro плейсхолдеры
            const compiledHtml = templateHtml.replace(/\{\{index\}\}/g, currentIndex.toString());

            // Append calculated fresh matrix row seamlessly into the operational DOM stack
            tbody.insertAdjacentHTML('beforeend', compiledHtml);
            updateRemoveButtonsState(tbody);
            return;
        }

        // 2. Handle interactive row removal execution block
        const removeBtn = target.closest('.mtx-remove-row-btn');
        if (removeBtn) {
            event.preventDefault();

            const row = removeBtn.closest('.mtx-dynamic-row');
            if (!row) return;

            const tbody = row.closest('.mtx-dynamic-rows-body');
            if (!tbody) return;

            // Ensure deletion halts if administrative safety guards are violated
            if (tbody.querySelectorAll('.mtx-dynamic-row').length <= 1) return;

            row.remove();

            // Re-index remaining fields sequentially to prevent missing array offsets on POST serialization
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

    // Initialize state validation routine on layout boot for all present tables
    document.querySelectorAll('.mtx-dynamic-rows-body').forEach(tbody => {
        updateRemoveButtonsState(tbody);
    });

    // 3. Handle Native WordPress Media Uploader Integration
    document.addEventListener('click', (event) => {
        const uploadBtn = event.target.closest('.mtx-media-upload-trigger');
        if (!uploadBtn) return;

        event.preventDefault();

        const targetInputId = uploadBtn.getAttribute('data-target');
        const targetInput = document.getElementById(targetInputId);
        if (!targetInput) return;

        // Instantiate and open the native WordPress media modal frame
        const frame = wp.media({
            title: 'Choose a Store Logo',
            button: {
                text: 'Use this image'
            },
            multiple: false // Enforce single asset selection context
        });

        // Capture selection state metadata once the user confirms their asset choice
        frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();

            // Populate our field with the clean absolute URL of the selected asset
            targetInput.value = attachment.url;

            // Trigger a native change event so any reliant validation hooks know data has mutated
            targetInput.dispatchEvent(new Event('change', { bubbles: true }));
        });

        frame.open();
    });

    // 4. Handle Live Media Preview Placement and Interactive State Actions Toggle
    document.addEventListener('click', (event) => {
        const target = event.target;

        // Trigger logic branch for asset removal execution routine
        const removeMediaBtn = target.closest('.mtx-media-remove-trigger');
        if (removeMediaBtn) {
            event.preventDefault();

            const targetInputId = removeMediaBtn.getAttribute('data-target');
            const targetInput = document.getElementById(targetInputId);
            const container = document.getElementById(`container_${targetInputId}`);
            if (!targetInput || !container) return;

            // Purge active data values and structural nodes properties representation
            targetInput.value = '';

            const previewBox = container.querySelector('.mtx-media-preview-box');
            if (previewBox) previewBox.style.display = 'none';

            // Roll button text properties down to initial states layout
            const uploadBtn = container.querySelector('.mtx-media-upload-trigger');
            if (uploadBtn) {
                const initialLabel = uploadBtn.getAttribute('data-label-upload') || 'Upload Logo';
                uploadBtn.textContent = initialLabel;
            }

            // Hide the remove action controller node itself
            removeMediaBtn.style.display = 'none';

            targetInput.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        // Intercept native media upload returns to feed structural nodes clones
        const uploadMediaBtn = target.closest('.mtx-media-upload-trigger');
        if (uploadMediaBtn) {
            // We hook directly into the 'select' logic we registered earlier to mutate the DOM properties
            const targetInputId = uploadMediaBtn.getAttribute('data-target');
            const container = document.getElementById(`container_${targetInputId}`);
            if (!container) return;

            // Wait for media framework modal selection payload cycles to update markup structure dynamically
            const targetInput = document.getElementById(targetInputId);
            if (!targetInput) return;

            const originalChangeHandler = () => {
                const newUrl = targetInput.value;
                if (newUrl === '') return;

                // Sync the image source paths across the mock nodes references inside visual frames
                container.querySelectorAll('.mtx-preview-image-node, .mtx-preview-favicon-node').forEach(img => {
                    img.setAttribute('src', newUrl);
                });

                // Display preview boxes viewport containers block elements maps
                const previewBox = container.querySelector('.mtx-media-preview-box');
                if (previewBox) previewBox.style.display = '';

                // Change active actions label triggers string values context
                const changeLabel = uploadMediaBtn.getAttribute('data-label-change') || 'Change Site Icon';
                uploadMediaBtn.textContent = changeLabel;

                // Make remove trigger component visible explicitly
                const removeBtn = container.querySelector('.mtx-media-remove-trigger');
                if (removeBtn) removeBtn.style.display = '';

                targetInput.removeEventListener('change', originalChangeHandler);
            };

            targetInput.addEventListener('change', originalChangeHandler);
        }
    });
});
