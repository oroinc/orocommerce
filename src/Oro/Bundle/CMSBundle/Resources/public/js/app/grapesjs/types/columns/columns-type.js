import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import ColumnsPresetView from './dialogs/columns-preset-view';
import ColumnsStyleManagerService from './services/columns-style-manager-service';
import columnsResizer from './mixins/columns-resizer';
import columnComponent from './mixins/column-component';
import columnType from './mixins/column-type';

const ColumnsType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.columns.label'),
        category: {
            label: __('oro.cms.wysiwyg.block_manager.categories.layout'),
            order: 1
        },
        attributes: {
            'class': 'fa fa-columns'
        },
        activate: true
    },

    modelProps: {
        defaults: {
            name: __('oro.cms.wysiwyg.component.columns.label'),
            classes: ['grid'],
            privateClasses: ['grid', 'grid-col'],
            unstylable: [
                'float', 'display', 'label-parent-flex', 'flex-direction',
                'justify-content', 'align-items', 'flex', 'align-self', 'order'
            ]
        },

        init() {
            this.styleManager = new ColumnsStyleManagerService({
                model: this,
                editor: this.editor
            });

            if (this.parent()?.is(this.get('type'))) {
                this.addClass('grid-col');
                this.set({
                    name: __('oro.cms.wysiwyg.component.columns.sub_columns_label'),
                    resizable: columnsResizer
                });
            }

            this.listenTo(this.get('components'), 'add', this.convertComponentTypeOnAdd);
        },

        convertComponentTypeOnAdd(child, components) {
            if (['columns-item', 'columns', 'div-block'].includes(child.get('type'))) {
                return;
            }

            setTimeout(() => {
                const item = components.add({
                    type: 'columns-item'
                }, {
                    at: child.index()
                });

                item.append(child);
            });
        },

        getColumnsCount() {
            const columnsCount = this.getStyle('--grid-column-count');

            if (!columnsCount) {
                return this.view.getComputedColumnsCount();
            }

            return columnsCount;
        },

        generateColumnsByPreset(preset) {
            if (!preset) {
                return;
            }

            this.get('components').add(preset.cols.map(col => {
                const child = {
                    type: 'columns-item'
                };

                if (col > 1) {
                    if (this.getCodeModeState()) {
                        child.classes = [`grid-col-${col}`];
                    } else {
                        child.style = {
                            '--grid-column-span': col
                        };
                    }
                }

                return child;
            }));

            if (this.getCodeModeState()) {
                this.addClass(`grid-columns-${preset.count}`);
            } else {
                this.setStyle({
                    '--grid-column-count': preset.count
                }, {
                    mediaText: ''
                });
            }
        },

        getColumnWidth() {
            return this.view.getComputedColumnWidth();
        },

        getColumnWidthWithGap() {
            const gap = this.getStyle('--grid-gap');

            if (!gap) {
                return this.view.getComputedColumnWidthWithGap();
            }

            return gap.split(' ')[1];
        },

        ...columnComponent
    },

    viewProps: {
        onActive() {
            this.editor.Commands.run('select-columns-preset', {
                model: this.model
            });
        },

        onRender() {
            this.$el.css('min-height', 50);
        },

        getComputedColumnsCount() {
            const columnsTpl = getComputedStyle(this.el).getPropertyValue('grid-template-columns');
            return columnsTpl.split(' ').length;
        },

        getComputedSpan() {
            const span = getComputedStyle(this.el).getPropertyValue('grid-column-end');

            if (span.startsWith('span')) {
                return parseInt(span.replace('span ', ''));
            }

            return 1;
        },

        getComputedColumnWidth() {
            const columnsTpl = getComputedStyle(this.el).getPropertyValue('grid-template-columns');
            return parseInt(columnsTpl.split(' ')[0]);
        },

        getComputedColumnWidthWithGap() {
            const columnGap = getComputedStyle(this.el).getPropertyValue('grid-column-gap');
            return this.getComputedColumnWidth() + parseInt(columnGap);
        }
    },

    commands: {
        'select-columns-preset': {
            run(editor, sender, {model}) {
                this.presetsDialog = new ColumnsPresetView({
                    component: model
                });
            },
            stop(editor, sender, {remove}) {
                if (!this.presetsDialog) {
                    return;
                }

                if (remove) {
                    const model = this.presetsDialog.component;
                    editor.selectRemove(model);
                    model.remove();
                }

                this.presetsDialog.dispose();
                delete this.presetsDialog;
            }
        }
    },

    ...columnType,

    constructor: function ColumnsTypeBuilder(...args) {
        ColumnsTypeBuilder.__super__.constructor.apply(this, args);
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.classList.contains('grid');
    }
}, {
    type: 'columns'
});

export default ColumnsType;
