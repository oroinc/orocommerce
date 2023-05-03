import __ from 'orotranslation/js/translator';
import BaseType from 'orocms/js/app/grapesjs/types/base-type';

const GRID_STYLES = `<style>
    .grid-row {
        display: table;
        table-layout: fixed;
        width: 100%;
        min-height: 75px;
    }
    .grid-cell {
        display: table-cell;
        width: 25%;
        vertical-align: top;
        padding-right: 4px;
        padding-left: 4px;
    }
</style>`;

const GridType = BaseType.extend({
    constructor: function GridTypeBuilder(options) {
        GridTypeBuilder.__super__.constructor.call(this, options);
    },

    execute() {
        const {BlockManager} = this.editor;

        BlockManager.add('column1', {
            label: '1 Column',
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 100%;"></div>
                        </div>${GRID_STYLES}`,
            attributes: {
                'class': 'gjs-fonts gjs-f-b1'
            },
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });

        BlockManager.add('column2', {
            label: '2 Column',
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 50%;"></div>
                            <div data-gjs-type="grid-column" style="width: 50%;"></div>
                        </div>${GRID_STYLES}`,
            attributes: {
                'class': 'gjs-fonts gjs-f-b2'
            },
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });

        BlockManager.add('column3', {
            label: '3 Column',
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 33.33%;"></div>
                            <div data-gjs-type="grid-column" style="width: 33.33%;"></div>
                            <div data-gjs-type="grid-column" style="width: 33.33%;"></div>
                        </div>${GRID_STYLES}`,
            attributes: {
                'class': 'gjs-fonts gjs-f-b3'
            },
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });

        BlockManager.add('column3-7', {
            label: '2 Column 3/7',
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 30%;"></div>
                            <div data-gjs-type="grid-column" style="width: 70%;"></div>
                        </div>${GRID_STYLES}`,
            attributes: {
                'class': 'gjs-fonts gjs-f-b37'
            },
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });
    },

    isComponent() {
        return false;
    }
}, {
    type: 'grid'
});

export default GridType;
