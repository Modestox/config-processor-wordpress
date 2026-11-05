/**
 * Modestox Config Processor Integration Wordpress
 *
 * @copyright Copyright (c) 2026 Sergey Kuzmitsky
 * @license   MIT
 * @link      https://github.com/Modestox/config-processor-wordpress
 */

/**
 * Behaviour of the dynamic rows, file and image fields.
 */
(() => {
    'use strict';

    // ----- Dynamic rows -----------------------------------------------------------------------

    /**
     * Rewrites the row index in the input names: option[3][column] -> option[0][column].
     *
     * @param {HTMLElement} tbody
     */
    const reindexRows = (tbody) => {
        tbody.querySelectorAll('.mtx-dynamic-row').forEach((row, index) => {
            row.querySelectorAll('input[name]').forEach((input) => {
                input.setAttribute('name', input.getAttribute('name').replace(/\[[^\]]*\]/, `[${index}]`));
            });
        });
    };

    /**
     * Up/down buttons are disabled at the edges and next to a locked row: free rows swap only with free rows.
     *
     * @param {HTMLElement} tbody
     */
    const updateMoveButtons = (tbody) => {
        tbody.querySelectorAll('.mtx-dynamic-row').forEach((row) => {
            const previous = row.previousElementSibling;
            const next = row.nextElementSibling;
            const up = row.querySelector('.mtx-move-row-btn[data-direction="up"]');
            const down = row.querySelector('.mtx-move-row-btn[data-direction="down"]');
            if (up) {
                up.disabled = !previous || previous.classList.contains('mtx-locked-row');
            }
            if (down) {
                down.disabled = !next || next.classList.contains('mtx-locked-row');
            }
        });
    };

    /**
     * @param {HTMLElement} moveButton
     */
    const moveRow = (moveButton) => {
        const row = moveButton.closest('.mtx-dynamic-row');
        const tbody = row ? row.closest('.mtx-dynamic-rows-body') : null;
        if (!row || !tbody || row.classList.contains('mtx-locked-row')) {
            return;
        }

        const goesUp = moveButton.getAttribute('data-direction') === 'up';
        const neighbour = goesUp ? row.previousElementSibling : row.nextElementSibling;
        if (!neighbour || neighbour.classList.contains('mtx-locked-row')) {
            return;
        }

        if (goesUp) {
            tbody.insertBefore(row, neighbour);
        } else {
            tbody.insertBefore(neighbour, row);
        }

        reindexRows(tbody);
        updateMoveButtons(tbody);
        moveButton.focus();
        tbody.dispatchEvent(new Event('change', {bubbles: true}));
    };

    /**
     * @param {HTMLElement} addButton
     */
    const addRow = (addButton) => {
        const tableId = addButton.getAttribute('data-table');
        const table = document.getElementById(tableId);
        const template = document.getElementById(`${tableId}-template`);
        const tbody = table ? table.querySelector('.mtx-dynamic-rows-body') : null;
        if (!tbody || !template) {
            return;
        }

        const index = tbody.querySelectorAll('.mtx-dynamic-row').length;
        tbody.insertAdjacentHTML('beforeend', template.innerHTML.replace(/\{\{index\}\}/g, String(index)));
        updateMoveButtons(tbody);
        tbody.dispatchEvent(new Event('change', {bubbles: true}));
    };

    /**
     * The last row is cleared instead of removed, so the table never disappears.
     *
     * @param {HTMLElement} removeButton
     */
    const removeRow = (removeButton) => {
        const row = removeButton.closest('.mtx-dynamic-row');
        const tbody = row ? row.closest('.mtx-dynamic-rows-body') : null;
        if (!row || !tbody) {
            return;
        }

        if (tbody.querySelectorAll('.mtx-dynamic-row').length <= 1) {
            row.querySelectorAll('input').forEach((input) => {
                input.value = '';
            });
        } else {
            row.remove();
            reindexRows(tbody);
            updateMoveButtons(tbody);
        }
        tbody.dispatchEvent(new Event('change', {bubbles: true}));
    };

    // ----- File / image -----------------------------------------------------------------------

    /**
     * @param {HTMLElement} box .mtx-media-field
     * @param {string} url
     */
    const setMedia = (box, url) => {
        const input = box.querySelector('.mtx-media-value');
        if (!input) {
            return;
        }
        input.value = url;

        const hasFile = url !== '';
        const preview = box.querySelector('.mtx-media-preview-box');
        if (preview) {
            preview.style.display = hasFile ? '' : 'none';
        }

        const image = box.querySelector('.mtx-preview-image-node');
        if (image) {
            image.setAttribute('src', url);
        }

        const link = box.querySelector('.mtx-media-file-link');
        if (link) {
            link.setAttribute('href', url);
            link.textContent = hasFile ? decodeURIComponent(url.split(/[?#]/)[0].split('/').pop() || url) : '';
        }

        const selectButton = box.querySelector('.mtx-media-select');
        if (selectButton) {
            selectButton.textContent = hasFile ? box.dataset.labelChange : box.dataset.labelSelect;
        }

        const removeButton = box.querySelector('.mtx-media-remove');
        if (removeButton) {
            removeButton.style.display = hasFile ? '' : 'none';
        }

        input.dispatchEvent(new Event('change', {bubbles: true}));
    };

    /**
     * @param {HTMLElement} box
     */
    const openMediaFrame = (box) => {
        if (typeof window.wp === 'undefined' || !window.wp.media) {
            console.error('Modestox: the WordPress media library is not loaded on this page.');
            return;
        }

        const options = {
            title: box.dataset.labelSelect || 'Select',
            button: {text: box.dataset.labelSelect || 'Select'},
            multiple: false,
        };
        if (box.dataset.kind === 'image') {
            options.library = {type: 'image'};
        }

        const frame = window.wp.media(options);
        frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();
            setMedia(box, attachment.url || '');
        });
        frame.open();
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.mtx-dynamic-rows-body').forEach(updateMoveButtons);
    });

    // ----- Click delegation -------------------------------------------------------------------

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        const addButton = target.closest('.mtx-add-row-btn');
        if (addButton) {
            event.preventDefault();
            addRow(addButton);
            return;
        }

        const moveButton = target.closest('.mtx-move-row-btn');
        if (moveButton) {
            event.preventDefault();
            moveRow(moveButton);
            return;
        }

        const removeButton = target.closest('.mtx-remove-row-btn');
        if (removeButton) {
            event.preventDefault();
            removeRow(removeButton);
            return;
        }

        const selectMedia = target.closest('.mtx-media-select');
        if (selectMedia) {
            event.preventDefault();
            openMediaFrame(selectMedia.closest('.mtx-media-field'));
            return;
        }

        const removeMedia = target.closest('.mtx-media-remove');
        if (removeMedia) {
            event.preventDefault();
            setMedia(removeMedia.closest('.mtx-media-field'), '');
        }
    });
})();