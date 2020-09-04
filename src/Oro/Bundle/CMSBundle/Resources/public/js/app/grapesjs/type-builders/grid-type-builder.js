import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const GRID_STYLES = `<style>
    .grid-row {
        display: table;
        width: 100%;
        table-layout: fixed;
        min-height: 75px;
    }
    .grid-cell {
        width: 25%;
        display: table-cell;
        vertical-align: top;
    }
    @media (max-width: 768px) {
        .grid-cell {
            width: 100%;
            display: block;
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
            content: `<div data-gjs-type="row">
                            <div data-gjs-type="column"></div>
                        </div>${GRID_STYLES}`
        });

        BlockManager.get('column2').set({
            content: `<div data-gjs-type="row">
                            <div data-gjs-type="column"></div>
                            <div data-gjs-type="column"></div>
                        </div>${GRID_STYLES}`
        });

        BlockManager.get('column3').set({
            content: `<div data-gjs-type="row">
                            <div data-gjs-type="column"></div>
                            <div data-gjs-type="column"></div>
                            <div data-gjs-type="column"></div>
                        </div>${GRID_STYLES}`
        });

        BlockManager.get('column3-7').set({
            content: `<div data-gjs-type="row">
                            <div data-gjs-type="column" style="width: 30%;"></div>
                            <div data-gjs-type="column" style="width: 70%;"></div>
                        </div>${GRID_STYLES}`
        });
    }
});

export default GridTypeBuilder;
