import __ from 'orotranslation/js/translator';
import BaseClass from 'oroui/js/base-class';

const SECTORS = {
    'columns-settings': {
        name: __('oro.cms.wysiwyg.style_manager.sectors.columns_settings'),
        open: true
    },
    'columns-item-settings': {
        name: __('oro.cms.wysiwyg.style_manager.sectors.columns_item_settings'),
        open: true
    },
    'sub-columns-settings': {
        name: __('oro.cms.wysiwyg.style_manager.sectors.sub_columns_settings'),
        open: true
    }
};

const ColumnsStyleManagerService = BaseClass.extend({
    type: null,

    model: null,

    constructor: function ColumnsStyleManagerService(...args) {
        ColumnsStyleManagerService.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.type = options.type;
        this.model = options.model;
        this.editor = options.editor;
        ColumnsStyleManagerService.__super__.initialize.call(this, options);
    },

    enableStyleSectors(opts = {}) {
        const {StyleManager} = this.editor;
        const sectorId = this.getSectorId();

        this.sector = StyleManager.addSector(sectorId, {
            ...SECTORS[sectorId],
            properties: this.getProps()
        }, {
            at: 0,
            ...opts
        });
    },

    disableStyleSectors() {
        if (!this.sector) {
            return;
        }

        const {StyleManager} = this.editor;
        StyleManager.removeSector(this.sector.get('id'));
        delete this.sector;
    },

    getSectorId() {
        const {model} = this;
        const parent = model.parent();
        const isParentGrid = parent?.is('columns');

        if (model.is('columns') && !isParentGrid) {
            return 'columns-settings';
        } else if (model.is('columns') && isParentGrid) {
            return 'sub-columns-settings';
        } else if (model.is('columns-item') && isParentGrid) {
            return 'columns-item-settings';
        }
    },

    getProps() {
        switch (this.getSectorId()) {
            case 'columns-settings':
                return this.getColumnsProps();
            case 'sub-columns-settings':
                return [...this.getColumnsProps(), ...this.getColumnsItemProps()];
            case 'columns-item-settings':
                return this.getColumnsItemProps();
        }
    },

    getColumnsProps() {
        const {model} = this;

        return [{
            property: '--grid-column-count',
            type: 'slider',
            name: __('oro.cms.wysiwyg.style_manager.properties.columns_count.name'),
            defaults: model.getColumnsCount(),
            step: 1,
            max: 12,
            min: 1
        }, {
            property: '--grid-gap',
            name: __('oro.cms.wysiwyg.style_manager.properties.gaps.name'),
            type: 'composite',
            properties: [{
                property: '--grid-row-gap',
                name: __('oro.cms.wysiwyg.style_manager.properties.row_gaps.name'),
                type: 'number',
                defaults: '16px',
                units: ['px', '%', 'em', 'rem', 'vh', 'vw'],
                min: 0,
                max: 1000
            }, {
                property: '--grid-column-gap',
                name: __('oro.cms.wysiwyg.style_manager.properties.column_gaps.name'),
                type: 'number',
                defaults: '16px',
                units: ['px', '%', 'em', 'rem', 'vh', 'vw'],
                min: 0,
                max: 1000
            }]
        }, {
            property: 'place-content',
            name: __('oro.cms.wysiwyg.style_manager.properties.place_content.name'),
            type: 'composite',
            properties: [{
                property: 'align-content',
                name: __('oro.cms.wysiwyg.style_manager.properties.align_content.name'),
                type: 'select',
                list: [{
                    value: 'start',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_start.name')
                }, {
                    value: 'center',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_center.name')
                }, {
                    value: 'end',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_end.name')
                }]
            }, {
                property: 'justify-content',
                name: __('oro.cms.wysiwyg.style_manager.properties.grid_justify_content.name'),
                type: 'select',
                list: [{
                    value: 'start',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_start.name')
                }, {
                    value: 'center',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_center.name')
                }, {
                    value: 'end',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_end.name')
                }]
            }]
        }, {
            property: 'place-items',
            name: __('oro.cms.wysiwyg.style_manager.properties.place_items.name'),
            type: 'composite',
            properties: [{
                property: 'align-items',
                name: __('oro.cms.wysiwyg.style_manager.properties.align_items.name'),
                type: 'select',
                list: [{
                    value: 'start',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_start.name')
                }, {
                    value: 'center',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_center.name')
                }, {
                    value: 'end',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_end.name')
                }]
            }, {
                property: 'justify-items',
                name: __('oro.cms.wysiwyg.style_manager.properties.justify_items.name'),
                type: 'select',
                list: [{
                    value: 'start',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_start.name')
                }, {
                    value: 'center',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_center.name')
                }, {
                    value: 'end',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_end.name')
                }]
            }]
        }];
    },

    getColumnsItemProps() {
        const {model} = this;
        return [{
            property: '--grid-column-span',
            type: 'slider',
            name: __('oro.cms.wysiwyg.style_manager.properties.column_span.name'),
            defaults: model.getSpan(),
            step: 1,
            max: model.parent().getColumnsCount(),
            min: 1
        }, {
            property: 'place-self',
            name: __('oro.cms.wysiwyg.style_manager.properties.place_self.name'),
            type: 'composite',
            properties: [{
                property: 'align-self',
                name: __('oro.cms.wysiwyg.style_manager.properties.align_self.name'),
                type: 'select',
                list: [{
                    value: 'start',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_start.name')
                }, {
                    value: 'center',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_center.name')
                }, {
                    value: 'end',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_end.name')
                }]
            }, {
                property: 'justify-self',
                name: __('oro.cms.wysiwyg.style_manager.properties.justify_self.name'),
                type: 'select',
                list: [{
                    value: 'start',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_start.name')
                }, {
                    value: 'center',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_center.name')
                }, {
                    value: 'end',
                    title: __('oro.cms.wysiwyg.style_manager.properties.grid_end.name')
                }]
            }]
        }];
    }
});

export default ColumnsStyleManagerService;
