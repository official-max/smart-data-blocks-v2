document.addEventListener('DOMContentLoaded', function () {
    // admin-fields

    const addButton = document.getElementById('sdb-add-field');
    const container = document.getElementById('sdb-fields-container');

    let fieldCount = container.querySelectorAll('.sdb-field-row').length;

    addButton.addEventListener('click', function () {
        const currentCount = container.querySelectorAll('.sdb-field-row').length;
        const newField = addNewField(currentCount);
        container.appendChild(newField);
    });



    // container.addEventListener('click', function (e) {
    //     // remove button logic for parent
    //     if (e.target.classList.contains('remove-field')) {
    //         e.target.closest('.sdb-field-row').remove();
    //     }
    // });





    // Field type change to Repeater UI handle
    container.addEventListener('change', function (e) {
        if (e.target.matches('select.sdb-field-type')) {
            const fieldRow = e.target.closest('.sdb-field-row');
            const selectedType = e.target.value;
            const depth = fieldRow.dataset.depth ? parseInt(fieldRow.dataset.depth) : 0;
            // Repeater select
            if (selectedType === 'repeater') {
                if (!fieldRow.querySelector('.sdb-repeater-settings')) {
                    const allFieldRows = [...document.querySelectorAll('.sdb-field-row')];
                    const fieldIndex = allFieldRows.indexOf(fieldRow); // ✅ Get correct index
                    addRepeater(fieldRow, fieldIndex);
                }
            } else {
                const existingSettings = fieldRow.querySelector('.sdb-repeater-settings');
                if (existingSettings) existingSettings.remove();
            }
        }
    });




    // Sub-field add/remove buttons
    container.addEventListener('click', function (e) {

        // remove button logic for parent
        if (e.target.classList.contains('remove-field')) {
            e.target.closest('.sdb-field-row').remove();
        }


        // Add Sub-Field
        if (e.target.classList.contains('sdb-add-sub-field')) {
            const subFieldsContainer = e.target.closest('.sdb-repeater-settings').querySelector('.sdb-sub-fields-list');
            const parentIndex = subFieldsContainer.closest('.sdb-field-row').dataset.index;
            const newIndex = subFieldsContainer.querySelectorAll('.sdb-sub-field-row').length;
            const newRow = getSubFieldRowHTML(parentIndex, newIndex);
            subFieldsContainer.insertAdjacentHTML('beforeend', newRow);
        }

        // Remove Sub-Field
        if (e.target.classList.contains('sdb-remove-sub-field')) {
            e.target.closest('.sdb-sub-field-row').remove();
        }
    });

});