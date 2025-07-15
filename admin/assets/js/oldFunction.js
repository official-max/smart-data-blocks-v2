// function.js
function createLocationRule(index) {
  const ruleDiv = document.createElement('div');
  ruleDiv.className = `sdb-location-rule rule-number-${index}`;

  // Param Dropdown
  const paramSelect = document.createElement('select');
  paramSelect.name = `location[${index}][param]`;

  const defaultOpt = new Option('---- Select ----', '', true, true); // new Option(text, value, defaultSelected, selected)
  defaultOpt.disabled = true;
  paramSelect.appendChild(defaultOpt);

  const locationParams = {
    'post_type': 'Post Type',
    'post': 'Page / Post',
    'page_template': 'Page Template',
    'user_role': 'User Role',
    'taxonomy': 'Taxonomy',
    'post_status': 'Post Status',
    'post_format': 'Post Format',
    'options_page': 'Options Page'
  };

  for (const key in locationParams) {
    paramSelect.appendChild(new Option(locationParams[key], key));
  }

  // Operator Dropdown
  const operatorSelect = document.createElement('select');
  operatorSelect.name = `location[${index}][operator]`;

  const operators = {
    '==': 'is equal to',
    '!=': 'is not equal to'
  };

  for (const key in operators) {
    operatorSelect.appendChild(new Option(operators[key], key));
  }

  // Value Select (initial empty)
  const valueSelect = document.createElement('select');
  valueSelect.name = `location[${index}][value]`;
  valueSelect.className = 'sdb-value-select';
  valueSelect.id = `sdb-value-select-${index}`;
  const valuePlaceholder = new Option('---- Select ----', '', true, true);
  valuePlaceholder.disabled = true;
  valueSelect.appendChild(valuePlaceholder);

  // Remove Button
  const removeButton = document.createElement('button');
  removeButton.type = 'button';
  removeButton.className = 'button';
  removeButton.textContent = '- Remove This Rule';
  removeButton.addEventListener('click', () => ruleDiv.remove());

  // Append all elements
  ruleDiv.appendChild(paramSelect);
  ruleDiv.appendChild(operatorSelect);
  ruleDiv.appendChild(valueSelect);
  ruleDiv.appendChild(removeButton);

  return ruleDiv;
}

function remove_button(button) {
  const ruleDiv = button.closest('.sdb-location-rule');
  if (ruleDiv) {
    ruleDiv.remove();
  }
}

// Repeater Settings UI Add
// function addRepeater(fieldRow, fieldIndex) {
//     const repeaterHTML = `
//         <div class="sdb-repeater-settings">
//             <h4>Type: Repeater</h4>
//             <div class="sdb-sub-fields" data-parent-index="${fieldIndex}">
//                 ${getSubFieldRowHTML(fieldIndex, 0)}
//             </div>
//             <button type="button" class="button sdb-add-sub-field">+ Add Sub Field</button>
//         </div>
//     `;
//     fieldRow.insertAdjacentHTML('beforeend', repeaterHTML);
// }

// Update the getSubFieldRowHTML function to support nesting
function getSubFieldRowHTML(parentIndex, subIndex) {
  return `
    <div class="sdb-sub-field sdb-sub-field-row" data-parent-index="${parentIndex}">
        <input type="text" name="fields[${parentIndex}][repeater_sub_fields][${subIndex}][label]" placeholder="Label">
        <input type="text" name="fields[${parentIndex}][repeater_sub_fields][${subIndex}][name]" placeholder="Name">
        <select name="fields[${parentIndex}][repeater_sub_fields][${subIndex}][type]" class="sdb-field-type">
            <option value="text">Text</option>
            <option value="textarea">Textarea</option>
            <option value="image">Image</option>
            <option value="gallery">Gallery</option>
            <option value="editor">Editor</option>
            <option value="repeater">Repeater</option>
        </select>
        <button type="button" class="button sdb-remove-sub-field">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
    `;
}

// Update the addRepeater function to handle depth
function addRepeater(fieldRow, fieldIndex, depth = 0) {
  const repeaterHTML = `
    <div class="sdb-repeater-settings" data-depth="${depth}">
      <div class="sdb-repeater-header">
        <h4><span class="dashicons dashicons-admin-generic"></span> Repeater Settings</h4>
      </div>
      <div class="sdb-repeater-options">
        <div class="sdb-option-row">
          <label>Minimum Rows:</label>
          <input type="number" name="fields[${fieldIndex}][repeater_min]" value="1" min="1">
        </div>
        <div class="sdb-option-row">
          <label>Maximum Rows:</label>
          <input type="number" name="fields[${fieldIndex}][repeater_max]" value="10" min="1">
        </div>
      </div>
      <div class="sdb-sub-fields-container">
        <h5>Sub Fields</h5>
        <div class="sdb-sub-fields-list">
          ${getSubFieldRowHTML(fieldIndex, 0)}
        </div>
        <button type="button" class="button button-primary sdb-add-sub-field">
          <span class="dashicons dashicons-plus"></span> Add Sub Field
        </button>
      </div>
    </div>
  `;
  fieldRow.insertAdjacentHTML('beforeend', repeaterHTML);
}

// parent Fields
function addNewField(fieldCount) {
  const newField = document.createElement('div');
  newField.className = 'sdb-field-row';
  newField.setAttribute('data-index', fieldCount);

  newField.innerHTML = `
    <div>
      <input type="text" name="fields[${fieldCount}][field_label]" placeholder="Field Label" required>
    </div>
    <div>
      <input type="text" name="fields[${fieldCount}][field_name]" placeholder="Field Name" required>
    </div>
    <div>
      <select name="fields[${fieldCount}][field_type]" required>
        <option value="text">Text</option>
        <option value="textarea">Textarea</option>
        <option value="image">Image</option>
        <option value="gallery">Gallery</option>
        <option value="editor">Editor</option>
        <option value="repeater">Repeater</option>
      </select>
    </div>
    <div>
      <button type="button" class="button button-danger remove-field">
        <span class="dashicons dashicons-trash"></span> Delete
      </button>
    </div>
  `;

  return newField;
}
