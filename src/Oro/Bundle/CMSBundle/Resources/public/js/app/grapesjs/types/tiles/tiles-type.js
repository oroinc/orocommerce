import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';
import TilesPresetView from './dialogs/tiles-preset-view';
import TilesStyleManagerService from './services/tiles-style-manager-service';

const TilesType = BaseType.extend({
    button: {
        label: __('oro.cms.wysiwyg.component.tiles.label'),
        category: {
            label: __('oro.cms.wysiwyg.block_manager.categories.layout'),
            order: 1
        },
        attributes: {
            'class': 'fa fa-th'
        },
        activate: true
    },

    modelProps: {
        defaults: {
            name: __('oro.cms.wysiwyg.component.tiles.label'),
            classes: ['tiles'],
            privateClasses: ['tiles'],
            unstylable: [
                'float', 'display', 'label-parent-flex', 'flex-direction',
                'justify-content', 'align-items', 'flex', 'align-self', 'order'
            ]
        },

        init() {
            this.styleManager = new TilesStyleManagerService({
                model: this,
                editor: this.editor
            });

            this.listenTo(this.get('components'), 'add', this.convertComponentTypeOnAdd);
        },

        convertComponentTypeOnAdd(child, components) {
            if (['tiles-item', 'div-block'].includes(child.get('type'))) {
                return;
            }

            setTimeout(() => {
                const item = components.add({
                    type: 'tiles-item'
                }, {
                    at: child.index()
                });

                item.append(child);
            });
        },

        generateTilesByPreset(preset) {
            if (!preset) {
                return;
            }

            this.get('components').add(new Array(preset.items).fill({
                type: 'tiles-item'
            }));

            this.setStyle({
                '--tiles-column-count': preset.count
            }, {
                mediaText: ''
            });
        },

        getTilesCount() {
            const tilesCount = this.getStyle('--tiles-column-count');

            if (!tilesCount) {
                return this.view.getComputedTilesCount();
            }

            return tilesCount;
        },

        getTilesRowGap() {
            const rowGap = this.getStyle('--tiles-row-gap');

            if (!rowGap) {
                return this.view.getComputedTilesRowGap();
            }

            return rowGap;
        },

        getTilesColumnGap() {
            const columnGap = this.getStyle('--tiles-column-gap');

            if (!columnGap) {
                return this.view.getComputedTilesColumnGap();
            }

            return columnGap;
        }
    },

    viewProps: {
        onActive() {
            this.editor.Commands.run('select-tiles-preset', {
                model: this.model
            });
        },

        onRender() {
            this.$el.css('min-height', 50);
        },

        getComputedTilesCount() {
            const width = parseInt(getComputedStyle(this.el).getPropertyValue('width'));

            const child = this.el.firstChild;

            if (child && child.nodeType === Node.ELEMENT_NODE && child.classList.contains('tiles-item')) {
                const childWidth = parseInt(getComputedStyle(child).getPropertyValue('width'));

                return Math.round(width / childWidth);
            }

            return '';
        },

        getComputedTilesRowGap() {
            const gap = getComputedStyle(this.el).getPropertyValue('gap');
            return parseInt(gap.split(' ')[0]);
        },

        getComputedTilesColumnGap() {
            const gap = getComputedStyle(this.el).getPropertyValue('gap');
            return parseInt(gap.split(' ')[1]);
        }
    },

    editorEvents: {
        'component:selected': 'onSelected',
        'component:deselected': 'onDeselected'
    },

    commands: {
        'select-tiles-preset': {
            run(editor, sender, {model}) {
                this.presetsDialog = new TilesPresetView({
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

    constructor: function TilesTypeBuilder(...args) {
        TilesTypeBuilder.__super__.constructor.apply(this, args);
    },

    onSelected(model) {
        if (!model.is(this.componentType)) {
            return;
        }

        model.styleManager.enableStyleSectors();
    },

    onDeselected(model) {
        if (!model.is(this.componentType)) {
            return;
        }

        model.styleManager.disableStyleSectors();
    },

    isComponent(el) {
        return el.nodeType === Node.ELEMENT_NODE && el.classList.contains('tiles');
    }
}, {
    type: 'tiles'
});

export default TilesType;
