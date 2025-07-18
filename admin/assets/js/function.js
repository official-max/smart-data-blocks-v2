// function.js

// Field-type choices
const FIELD_TYPES = [
  ['text', 'Text'],
  ['textarea', 'Textarea'],
  ['image', 'Image'],
  ['gallery', 'Gallery'],
  ['editor', 'Editor'],
  ['repeater', 'Repeater'],
  ['file', 'File'],
];

// -----------------------------------------------------------------------------
// Helper utilities
// -----------------------------------------------------------------------------

/** Slugify text – e.g. "My Label" => "my_label" */
function slugify(text) {
  return text
    .toLowerCase()
    .replace(/\s+/g, '_')        // Replace spaces with _
    .replace(/[^\w_]+/g, '')     // Remove non-word chars except _
    .replace(/__+/g, '_')        // Replace multiple _ with single _
    .replace(/^_+|_+$/g, '');    // Trim _ from start and end
}


/** Unique name banaye [jab tak name math hota rahe ga loop chalta rahe ga last n plug hora hai] */
function uniqueSlug(base, siblingInputs) {
  let slug = base;
  let n = 2;
  // jab tak same naam kisi sibling <input name$="[name]"> me mil raha hai
  while ([...siblingInputs].some(i => i.value === slug)) {
    slug = base + '_' + n;
    n++;
  }
  return slug;
}

/**
 * Create an <option> element
 */
const opt = (txt, val, sel = false) =>
  Object.assign(document.createElement('option'), {
    textContent: txt,
    value: val,
    selected: sel,
  });

/** Path builder – nested repeater ke liye sub-field ka path */
function buildChildPath(parentRow, index) {
  const parentPath = parentRow.dataset.path; // e.g. fields[0][settings][sub_fields][1]
  return `${parentPath}[settings][sub_fields][${index}]`;
}

/** Depth apply karne se css seh depth update kr rahe hai ui k liye */
function applyDepth(row, depth) {
  row.dataset.depth = depth;
  row.style.setProperty('--depth', depth);
}


// -----------------------------------------------------------------------------
// Templating helpers – return ready DOM nodes
// -----------------------------------------------------------------------------

function makeSelect(name, current = 'text', addClass = '') {
  const sel = Object.assign(document.createElement('select'), {
    name,
    className: addClass ? addClass : '',
  });
  FIELD_TYPES.forEach(([val, label]) =>
    sel.appendChild(opt(label, val, val === current)),
  );
  return sel;
}

function makeInput(name, placeholder = '', type = 'text') {
  return Object.assign(document.createElement('input'), {
    name,
    placeholder,
    type,
    required: true,
  });
}

function makeIconButton(text, klass = '', icon = '') {
  const btn = Object.assign(document.createElement('button'), {
    type: 'button',
    className: `button ${klass}`.trim(),
  });
  if (icon) {
    const span = document.createElement('span');
    span.className = `dashicons ${icon}`;
    btn.appendChild(span);
  }
  btn.append(` ${text}`);
  return btn;
}

// -----------------------------------------------------------------------------
// Row builders
// -----------------------------------------------------------------------------

/**
 * Create a top-level field row
 */
function createFieldRow(index) {
  const row = document.createElement('div');
  row.className = 'sdb-field-row';
  row.dataset.index = index;
  row.dataset.path = `fields[${index}]`;
  applyDepth(row, 0);

  // Field Label Input
  row.appendChild(makeInput(`fields[${index}][label]`, 'Field Label'));

  // Field Name Input
  row.appendChild(makeInput(`fields[${index}][name]`, 'Field Name'));

  // Field Type Select
  const sel = makeSelect(`fields[${index}][type]`, 'text', 'sdb-field-type');
  row.appendChild(sel);

  // Add hidden order input
  const orderInput = document.createElement('input');
  orderInput.type = 'hidden';
  orderInput.name = `fields[${index}][field_order]`;
  orderInput.value = index;
  row.appendChild(orderInput);

  // Delete Button
  const btn = makeIconButton('Delete', 'button-danger remove-field', 'dashicons-trash');
  row.appendChild(btn);

  return row;
}


/**
 * Create a repeater settings block inside a fieldRow
 */
function createRepeaterSettings(parentRow) {
  const depth = Number(parentRow.dataset.depth) + 1;
  const parentPath = parentRow.dataset.path;
  const settingIdx = 0; // first sub-field
  const childPath = `${parentPath}[settings][sub_fields][${settingIdx}]`;

  const wrap = document.createElement('div');
  wrap.className = 'sdb-repeater-settings';

  // HeadercreateRepeaterSettings
  wrap.insertAdjacentHTML(
    'beforeend',
    `<div class="sdb-repeater-header">
        <h4><span class="dashicons dashicons-admin-generic"></span> Repeater Settings</h4>
     </div>`,
  );

  // Min / Max
  const options = document.createElement('div');
  options.className = 'sdb-repeater-options';
  options.innerHTML = `
       <div class="sdb-option-row">
          <label>Minimum Rows:</label>
          <input type="number" name="${parentPath}[settings][min_rows]" value="1" min="0">
       </div>
       <div class="sdb-option-row">
          <label>Maximum Rows:</label>
          <input type="number" name="${parentPath}[settings][max_rows]" value="10" min="1">
       </div>`;
  wrap.appendChild(options);

  // Sub-fields container
  const subContainer = document.createElement('div');
  subContainer.className = 'sdb-sub-fields-container';
  subContainer.innerHTML = '<h5>Sub Fields</h5>';
  const list = document.createElement('div');
  list.className = 'sdb-sub-fields-list';
  subContainer.appendChild(list);
  wrap.appendChild(subContainer);

  // First sub-field row
  list.appendChild(createSubFieldRow(parentRow, 0));

  // Add sub-field button
  const addBtn = makeIconButton('Add Sub Field', 'sdb-add-sub-field', 'dashicons-plus');
  subContainer.appendChild(addBtn);

  return { wrap, list };
}

/**
 * Create a sub-field row (can itself become repeater later)
 */
function createSubFieldRow(parentRow, subIndex) {
  const parentPath = parentRow.dataset.path;
  const depth = Number(parentRow.dataset.depth) + 1;
  const row = document.createElement('div');
  row.className = 'sdb-sub-field-row';
  applyDepth(row, depth);

  const path = buildChildPath(parentRow, subIndex);
  row.dataset.path = path;

  // Directly append elements without wrapping divs
  row.appendChild(makeInput(`${path}[label]`, 'Label'));
  row.appendChild(makeInput(`${path}[name]`, 'Name'));
  row.appendChild(makeSelect(`${path}[type]`, 'text', 'sdb-field-type'));
  row.appendChild(makeIconButton('', 'sdb-remove-sub-field button-danger', 'dashicons-trash'));

  // hidden order input
  // const orderInput = document.createElement('input');
  // orderInput.type = 'hidden';
  // orderInput.name = `${path}[field_order]`;
  // orderInput.value = subIndex;
  // row.appendChild(orderInput);

  return row;
}

/*
* Update field order value update
*/
// Drag fields For Top level
function updateFieldOrder(fieldCreator) {
  const rows = fieldCreator.querySelectorAll('.sdb-field-row');
  rows.forEach((row, newIndex) => {
    const input = row.querySelector('input[name$="[field_order]"]');
    if (input) input.value = newIndex;
  });
}

// Drag-&-drop inside every repeater
function initSubFieldSortable(listEl) {
  Sortable.create(listEl, {
    animation: 150,
    handle: '.sdb-sub-field-row',
    ghostClass: 'sortable-ghost',
    onEnd() {
      updateSubFieldOrders(listEl);
    }
  });
}

/* update hidden [field_order] inputs inside this list */
function updateSubFieldOrders(listEl) {
  [...listEl.querySelectorAll('.sdb-sub-field-row')].forEach((row, idx) => {
    const input = row.querySelector('input[name$="[field_order]"]');
    if (input) input.value = idx;
  });
}

// Create location Rule
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

// remove rules
function remove_button(button) {
  const ruleDiv = button.closest('.sdb-location-rule');
  if (ruleDiv) {
    ruleDiv.remove();
  }
}
