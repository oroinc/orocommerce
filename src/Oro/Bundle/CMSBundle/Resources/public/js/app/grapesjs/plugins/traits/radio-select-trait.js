import _ from 'underscore';
import itemTemplate from 'tpl-loader!orocms/templates/traits/radio-select-item.html';

export default function radioSelectTraitInit({editor}) {
    const ppfx = editor.getConfig('stylePrefix') || 'gjs-';
    const MAX_RADIO_OPTIONS = 3;

    editor.Traits.addType('radio-select', {
        noLabel: false,
        templateInput: '',

        createInput({trait}) {
            const options = trait.get('options') || [];

            this.inputType = options.length <= MAX_RADIO_OPTIONS ? 'radio' : 'select';

            return this.inputType === 'radio'
                ? this.createRadioInput(options, trait)
                : this.createSelectInput(options, trait);
        },

        createRadioInput(options, trait) {
            const name = trait.get('name') + '_' + _.uniqueId();
            const container = document.createElement('div');

            container.classList.add(`${ppfx}field-radio`);

            const items = document.createElement('div');

            items.classList.add(`${ppfx}radio-items`);

            options.forEach(opt => {
                const id = typeof opt === 'string' ? opt : opt.id;
                const label = typeof opt === 'string' ? opt : (opt.label || opt.id);
                const inputId = `${name}_${id}`;

                const wrapper = document.createElement('div');

                wrapper.innerHTML = itemTemplate({ppfx, name, id, inputId, label});

                const item = wrapper.firstElementChild;

                item.querySelector('input').addEventListener('change', () => {
                    trait.setValue(id);
                });

                items.appendChild(item);
            });

            container.appendChild(items);

            return container;
        },

        createSelectInput(options, trait) {
            const select = document.createElement('select');

            select.classList.add(`${ppfx}field`);

            options.forEach(opt => {
                const id = typeof opt === 'string' ? opt : opt.id;
                const label = typeof opt === 'string' ? opt : (opt.label || opt.id);
                const option = document.createElement('option');

                option.value = id;
                option.textContent = label;
                select.appendChild(option);
            });

            select.addEventListener('change', () => {
                trait.setValue(select.value);
            });

            return select;
        },

        onUpdate({elInput, trait}) {
            const value = trait.getInitValue();

            if (this.inputType === 'radio') {
                const radios = elInput.querySelectorAll('input[type="radio"]');

                radios.forEach(r => {
                    r.checked = r.value === value;
                });
            } else {
                elInput.value = value;
            }
        }
    });
}
