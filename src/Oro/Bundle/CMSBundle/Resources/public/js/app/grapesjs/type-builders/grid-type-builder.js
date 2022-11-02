import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

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
    @media (max-width: 768px) {
        .grid-cell {
            display: block;
            width: 100%;
            margin-left: 0;
            margin-bottom: 8px;
        }
    }
</style>`;

const GridTypeBuilder = BaseTypeBuilder.extend({
    constructor: function GridTypeBuilder(options) {
        GridTypeBuilder.__super__.constructor.call(this, options);
    },

    execute() {
        const {BlockManager} = this.editor;

        BlockManager.get('column1').set({
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 100%;"></div>
                        </div>${GRID_STYLES}`,
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });

        BlockManager.get('column2').set({
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 50%;"></div>
                            <div data-gjs-type="grid-column" style="width: 50%;"></div>
                        </div>${GRID_STYLES}`,
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });

        BlockManager.get('column3').set({
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 33.33%;"></div>
                            <div data-gjs-type="grid-column" style="width: 33.33%;"></div>
                            <div data-gjs-type="grid-column" style="width: 33.33%;"></div>
                        </div>${GRID_STYLES}`,
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });

        BlockManager.get('column3-7').set({
            content: `<div data-gjs-type="grid-row">
                            <div data-gjs-type="grid-column" style="width: 30%;"></div>
                            <div data-gjs-type="grid-column" style="width: 70%;"></div>
                        </div>${GRID_STYLES}`,
            category: {
                label: __('oro.cms.wysiwyg.block_manager.categories.legacy'),
                order: 200,
                open: false
            }
        });
    }
});

export default GridTypeBuilder;
