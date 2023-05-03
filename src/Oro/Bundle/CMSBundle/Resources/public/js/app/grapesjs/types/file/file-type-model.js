import __ from 'orotranslation/js/translator';
import openDigitalAssetsManager from './open-digital-assets-manager';

export default (BaseTypeModel, {editor}) => {
    const FileTypeModel = BaseTypeModel.extend({
        editor,

        constructor: function FileTypeModel(...args) {
            return FileTypeModel.__super__.constructor.apply(this, args);
        },

        init() {
            const toolbar = this.get('toolbar');
            if (!toolbar.find(toolbar => toolbar.id === 'file-settings')) {
                this.set('toolbar', [
                    {
                        attributes: {
                            'class': 'fa fa-gear',
                            'label': __('oro.cms.wysiwyg.toolbar.fileSetting')
                        },
                        id: 'file-settings',
                        command(editor) {
                            const selected = editor.getSelected();
                            if (selected) {
                                openDigitalAssetsManager(selected);
                            }
                        }
                    },
                    ...toolbar
                ]);
            }
        },

        /**
         * Returns object of attributes for HTML
         * @return {Object}
         * @private
         */
        getAttrToHTML: function(...args) {
            const attr = FileTypeModel.__super__.getAttrToHTML.apply(this, args);

            ['href', 'title'].forEach(attributeName => {
                const attributeValue = this.get(attributeName);
                if (attributeValue) {
                    attr[attributeName] = attributeValue;
                }
            });

            return attr;
        }
    });

    Object.defineProperty(FileTypeModel.prototype, 'defaults', {
        value: {
            ...FileTypeModel.prototype.defaults,
            'type': 'file',
            'tagName': 'a',
            'classes': ['digital-asset-file', 'no-hash'],
            'activeOnRender': 1,
            'void': 0,
            'droppable': 1,
            'editable': 1,
            'highlightable': 0,
            'resizable': 0,
            'traits': ['title', 'text', 'target']
        }
    });

    return FileTypeModel;
};
