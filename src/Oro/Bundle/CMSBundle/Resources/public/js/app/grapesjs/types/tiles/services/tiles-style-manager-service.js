import __ from 'orotranslation/js/translator';
import BaseClass from 'oroui/js/base-class';

const TilesStyleManagerService = BaseClass.extend({
    editor: null,

    model: null,

    constructor: function TilesStyleManagerService(...args) {
        TilesStyleManagerService.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        this.model = options.model;
        this.editor = options.editor;
        TilesStyleManagerService.__super__.initialize.call(this, options);
    },

    enableStyleSectors(opts = {}) {
        const {StyleManager} = this.editor;

        this.sector = StyleManager.addSector('tiles-settings', {
            name: __('oro.cms.wysiwyg.style_manager.sectors.tiles_settings'),
            open: true,
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

    getProps() {
        const {model} = this;

        return [
            {
                property: '--tiles-column-count',
                type: 'slider',
                name: __('oro.cms.wysiwyg.style_manager.properties.tiles_count.name'),
                defaults: model.getTilesCount(),
                step: 1,
                max: 12,
                min: 1
            }, {
                property: '--tiles-row-gap',
                name: __('oro.cms.wysiwyg.style_manager.properties.tiles_row_gaps.name'),
                type: 'number',
                defaults: model.getTilesRowGap(),
                units: ['px', '%', 'em', 'rem', 'vh', 'vw'],
                min: 0,
                max: 1000
            }, {
                property: '--tiles-column-gap',
                name: __('oro.cms.wysiwyg.style_manager.properties.tiles_column_gaps.name'),
                type: 'number',
                defaults: model.getTilesColumnGap(),
                units: ['px', '%', 'em', 'rem', 'vh', 'vw'],
                min: 0,
                max: 1000
            }
        ];
    }
});

export default TilesStyleManagerService;
