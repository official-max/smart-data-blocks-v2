// Smart-Data-Blocks\admin\assets\js\admin-fields.js
// -----------------------------------------------------------------------------
// DOM ready – attach behaviour
// -----------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('sdb-fields-container');
    const fieldCreator = container.querySelector('.sdb-field-creator');
    const addFieldBtn = document.getElementById('sdb-add-field');

    /* ── Add top-level field */
    addFieldBtn.addEventListener('click', () => {
        const index = container.querySelectorAll('.sdb-field-row[data-depth="0"]').length;
        fieldCreator.appendChild(createFieldRow(index));
    });

    /* ── Drag-drop ordering (top-level) */
    if (fieldCreator && window.Sortable) {
        Sortable.create(fieldCreator, {
            animation: 150,
            handle: '.sdb-field-row', // optional: click-drag whole row
            ghostClass: 'sortable-ghost',
            onEnd: () => {
                updateFieldOrder(fieldCreator);
            }
        });
    }

    // ----------------------------------------------
    // 🧲 Drag-drop for sub-fields (inside repeaters)
    // ----------------------------------------------
    document.querySelectorAll('.sdb-sub-fields-list').forEach(el => initSubFieldSortable(el));



    // ── On field type change → add repeater settings
    container.addEventListener('change', (e) => {
        if (!e.target.matches('select.sdb-field-type')) return;

        const row = e.target.closest('.sdb-field-row, .sdb-sub-field-row');
        const type = e.target.value;

        if (type === 'repeater' && !row.querySelector('.sdb-repeater-settings')) {
            const { wrap } = createRepeaterSettings(row);
            row.appendChild(wrap);
        } else if (type !== 'repeater') {
            const settings = row.querySelector('.sdb-repeater-settings');
            if (settings) settings.remove();
        }
    });

    // ── Remove field / sub-field
    container.addEventListener('click', (e) => {
        if (e.target.closest('.remove-field, .sdb-remove-sub-field')) {
            const row = e.target.closest('.sdb-field-row, .sdb-sub-field-row');
            row.remove();
            return;
        }

        // Add sub-field inside repeater
        if (e.target.classList.contains('sdb-add-sub-field')) {
            const subList = e.target.closest('.sdb-sub-fields-container').querySelector('.sdb-sub-fields-list');
            const parentRow = subList.closest('.sdb-field-row, .sdb-sub-field-row');
            const newIndex = subList.children.length;
            subList.appendChild(createSubFieldRow(parentRow, newIndex));
        }
    });


    // ----------------------------------------------
    // 🧠 Auto-create field name from label (slugify)
    // ----------------------------------------------
    container.addEventListener('input', (e) => {
        if (!e.target.matches('input[name$="[label]"]')) return;

        const row = e.target.closest('.sdb-field-row, .sdb-sub-field-row');
        const nameInput = row.querySelector('input[name$="[name]"]');
        if (!nameInput || (nameInput.value && nameInput.dataset.autogen !== 'true')) return;

        const base = slugify(e.target.value.trim());
        const siblings = row.parentElement.querySelectorAll('input[name$="[name]"]');
        nameInput.value = uniqueSlug(base, siblings);
        nameInput.dataset.autogen = 'true';
    });

    // Remove autogen flag if user edits name manually
    container.addEventListener('input', (e) => {
        if (e.target.matches('input[name$="[name]"]')) {
            e.target.dataset.autogen = 'false';
        }
    });

});