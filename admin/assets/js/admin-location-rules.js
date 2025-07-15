// File: js/admin-location-rules.js

// document.addEventListener('DOMContentLoaded', () => {
//     const rulesContainer = document.getElementById('sdb-location-rules');
//     const addButton = document.getElementById('add-location-rule');

//     if (!rulesContainer || !addButton) return;

//     // 🔘 Add initial rule only if none exists
//     if (rulesContainer.querySelectorAll('.sdb-location-rule').length === 0) {
//         const rule = createLocationRule(0);
//         rulesContainer.appendChild(rule);
//     }

//     // 🔘 Add Rule button clicked
//     addButton.addEventListener('click', () => {
//         const index = rulesContainer.querySelectorAll('.sdb-location-rule').length;
//         const newRule = createLocationRule(index);
//         rulesContainer.appendChild(newRule);
//     });

//     // 🔁 Listener for dropdown change
//     rulesContainer.addEventListener('change', async (e) => {
//         if (e.target.matches('select[name*="[param]"]')) {
//             const paramSelect = e.target;
//             const selectedParam = paramSelect.value;

//             const wrapper = paramSelect.closest('.sdb-location-rule');
//             const valueField = wrapper.querySelector('.sdb-value-select, input[name*="[value]"]');

//             // If "options_page" selected → show text input
//             if (selectedParam === 'options_page') {
//                 const input = document.createElement('input');
//                 input.type = 'text';
//                 input.name = valueField.name;
//                 input.placeholder = 'Enter options page slug';
//                 input.value = '';
//                 valueField.replaceWith(input);
//                 return;
//             }

//             // AJAX: fetch value options
//             const response = await fetch(ajaxurl, {
//                 method: 'POST',
//                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//                 body: new URLSearchParams({
//                     action: 'sdb_get_location_values',
//                     param: selectedParam,
//                 })
//             });

//             const options = await response.json(); // [{ value, label }]

//             // Replace with new select
//             if (Array.isArray(options)) {
//                 const select = document.createElement('select');
//                 select.className = 'sdb-value-select';
//                 select.name = valueField.name;

//                 const placeholder = new Option('---- Select ----', '', true, true);
//                 placeholder.disabled = true;
//                 select.appendChild(placeholder);

//                 options.forEach(opt => {
//                     const option = document.createElement('option');
//                     option.value = opt.value;
//                     option.textContent = opt.label;
//                     select.appendChild(option);
//                 });

//                 valueField.replaceWith(select);
//             }
//         }
//     });
// });


document.addEventListener('DOMContentLoaded', function () {

    const rulesContainer = document.getElementById('sdb-location-rules');
    const addButton = document.getElementById('add-location-rule');

    if (rulesContainer.children.length === 0) { // yeah optional hai bcz phele he html seh created hai
        const firstRule = createLocationRule(0);
        rulesContainer.appendChild(firstRule);
    }


    addButton.addEventListener('click', function () {
        const newIndex = rulesContainer.children.length; // Check indexing
        const newRule = createLocationRule(newIndex);
        rulesContainer.appendChild(newRule);
    });


    rulesContainer.addEventListener('change', async function (e) {
        if (e.target.matches('select[name*="[param]"]')) {
            const paramSelect = e.target; // Target jis mai change hua hai
            const selectedParam = paramSelect.value;
            const ruleDiv = paramSelect.closest('.sdb-location-rule');
            const valueSelect = ruleDiv.querySelector('select[name*="[value]"]');



            // Agar "options_page" select kiya, toh input field show ho
            if (selectedParam === 'options_page') {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = valueSelect.name;
                input.placeholder = 'Enter options page slug';
                valueSelect.replaceWith(input);
                return;
            }

            // 8. Fetch Dropdown Value
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'sdb_get_location_values',
                    param: selectedParam
                })
            });
            const options = await response.json();

            // New Dropdown
            const newSelect = document.createElement('select');
            newSelect.name = valueSelect.name;
            newSelect.innerHTML = '<option disabled selected>---- Select ----</option>';

            options.forEach(option => {
                newSelect.appendChild(new Option(option.label, option.value));
            });

            valueSelect.replaceWith(newSelect);

        }
    });

});