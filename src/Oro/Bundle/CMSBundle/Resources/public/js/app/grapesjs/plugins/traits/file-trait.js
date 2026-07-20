import __ from 'orotranslation/js/translator';
import template from 'tpl-loader!orocms/templates/traits/file-trait.html';

export default function fileTraitInit({editor}) {
    const ppfx = editor.getConfig('stylePrefix') || 'gjs-';

    editor.Traits.addType('file', {
        noLabel: false,
        templateInput: '',

        createInput({trait, component}) {
            const buttonText = trait.get('buttonText') ||
                __('oro.cms.wysiwyg.component.link.choose_file');

            const container = document.createElement('div');

            container.innerHTML = template({ppfx, buttonText});

            const chooseBtn = container.querySelector('[data-action="choose"]');
            const clearBtn = container.querySelector('[data-action="clear"]');

            chooseBtn.addEventListener('click', () => this.onChoose());
            clearBtn.addEventListener('click', () => this.onClear());

            this.fileNameEl = container.querySelector('[data-role="file-name"]');
            this.infoEl = container.querySelector(`.${ppfx}file-trait-info`);

            this.listenTo(component, 'change:fileName', (model, value) => {
                if (value) {
                    this.showFileName(value);
                } else {
                    this.hideFileName();
                }
            });

            const fileName = component.get('fileName');

            if (fileName) {
                this.showFileName(fileName);
            }

            return container;
        },

        onChoose() {
            const trait = this.model;
            const component = this.target;
            const routeName = trait.get('routeName') ||
                'oro_digital_asset_widget_choose_file';
            const dialogTitle = trait.get('dialogTitle') ||
                __('oro.cms.wysiwyg.digital_asset.file.title');
            const onFileSelect = trait.get('onFileSelect');

            component.em.get('Commands').run(
                'open-digital-assets',
                {
                    target: component,
                    title: dialogTitle,
                    routeName: routeName,
                    onSelect: digitalAssetModel => {
                        const metadata = digitalAssetModel.get('previewMetadata');

                        if (onFileSelect) {
                            onFileSelect(component, metadata);
                        }
                    }
                }
            );
        },

        onClear() {
            const trait = this.model;
            const component = this.target;
            const onFileClear = trait.get('onFileClear');

            if (onFileClear) {
                onFileClear(component);
            }
        },

        showFileName(name) {
            if (this.fileNameEl && this.infoEl) {
                this.fileNameEl.textContent = name;
                this.infoEl.style.display = '';
            }
        },

        hideFileName() {
            if (this.fileNameEl && this.infoEl) {
                this.fileNameEl.textContent = '';
                this.infoEl.style.display = 'none';
            }
        }
    });
}
