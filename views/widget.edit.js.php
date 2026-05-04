<?php declare(strict_types = 0); ?>
window.widget_form = new class extends CWidgetForm {

    #form;

    init() {
        this.#form = this.getForm();
        const $multiselect = jQuery('#itemids_');
        $multiselect.on('change', () => this.#updateCustomNames());
        this.#updateCustomNames();
        this.ready();
    }

    #updateCustomNames() {
        const container = document.getElementById('custom-names-container');
	    const hiddenField = document.getElementById('custom_names_json');
        const $multiselect = jQuery('#itemids_');
        
        const selectedItems = $multiselect.multiSelect('getData');
        let currentData = {};
        
        try {
            currentData = JSON.parse(hiddenField.value || '{}');
        } catch (e) {
            currentData = {};
        }

        container.innerHTML = '';

        selectedItems.forEach(item => {
            const div = document.createElement('div');
            div.className = 'item-setting-row';
            div.style = 'margin-bottom: 8px; display: flex; align-items: center; gap: 10px;';
            
            const itemName = document.createElement('span');
            itemName.innerText = item.name;
            itemName.style = 'flex: 1; font-size: 11px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;';
            const label = document.createElement('label');

            const checkboxId = `binary_${item.id}`;
            label.setAttribute('for', checkboxId);
            label.innerHTML = '<span></span>' + 'Binary';
            label.style = 'cursor: pointer; white-space: nowrap;';
            
            const cbContainer = document.createElement('div');
            cbContainer.style = 'display: flex; align-items: center; min-width: 90px;';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = checkboxId; 
            checkbox.className = 'binary-checkbox checkbox-radio';
            
            const itemConfig = typeof currentData[item.id] === 'object' 
                ? currentData[item.id] 
                : { name: currentData[item.id] || '', binary: true };

            checkbox.checked = itemConfig.binary !== undefined ? itemConfig.binary : true;
            cbContainer.appendChild(checkbox);
            cbContainer.appendChild(label);
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'custom-name-input input';
            input.dataset.itemid = item.id;
            input.value = itemConfig.name || '';

            input.style = 'flex: 2;';
            input.placeholder = 'Default name';
            
            const saveAll = () => {
                const data = {};
                container.querySelectorAll('div.item-setting-row').forEach(row => {
                    const rowInput = row.querySelector('.custom-name-input');
                    const rowCheckbox = row.querySelector('.binary-checkbox');
                    const id = rowInput.dataset.itemid;
                    
                    data[id] = {
                        name: rowInput.value.trim(),
                        binary: rowCheckbox.checked
                    };
                });

                hiddenField.value = JSON.stringify(data);
                hiddenField.dispatchEvent(new CustomEvent('change', {bubbles: true}));
            };

            input.addEventListener('input', saveAll);
            checkbox.addEventListener('change', saveAll);
            
            div.appendChild(itemName);
            div.appendChild(input);
            div.appendChild(cbContainer);
            container.appendChild(div);
        });
    }
};
