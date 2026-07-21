import __ from 'orotranslation/js/translator';
import template from 'tpl-loader!orocms/templates/traits/href-trait.html';

/**
 * Trait type that combines a free-text URL input with a small file-picker icon.
 * The input edits the `href` attribute directly; the icon opens the digital
 * assets file dialog to pick a file URL.
 */
export default function hrefTraitInit({editor}) {
    const ppfx = editor.getConfig('stylePrefix') || 'gjs-';

    editor.Traits.addType('href', {
        noLabel: false,
        templateInput: '',

        createInput({trait, component}) {
            const chooseTitle = trait.get('chooseTitle') ||
                __('oro.cms.wysiwyg.component.link.choose_file');

            const container = document.createElement('div');

            container.innerHTML = template({ppfx, chooseTitle});

            this.inputEl = container.querySelector('[data-role="href"]');
            this.inputEl.value = trait.getInitValue() || '';

            this.inputEl.addEventListener('change', () => {
                trait.setValue(this.inputEl.value);
            });

            this.listenTo(component, 'change:attributes:href', () => {
                this.inputEl.value = component.getAttributes().href || '';
            });

            container.querySelector('[data-action="choose"]')
                .addEventListener('click', () => this.onChoose());

            return container;
        },

        onChoose() {
            const component = this.target;

            if (component && component.openFilePicker) {
                component.openFilePicker();
            }
        },

        onUpdate({trait}) {
            if (this.inputEl) {
                this.inputEl.value = trait.getInitValue() || '';
            }
        }
    });
}
