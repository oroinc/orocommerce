define(function(require) {
    'use strict';

    const ThemeSelectorView = require('orocms/js/app/grapesjs/controls/theme-selector-view');
    const $ = require('jquery');
    const _ = require('underscore');

    /**
     * Create panel manager instance
     * @param options
     * @constructor
     */
    const PanelManagerModule = function(options) {
        _.extend(this, _.pick(options, ['builder', 'themes']));

        this.init();
    };

    /**
     * Reposition and change builder panels
     * @type {{builder: null, init: init}}
     */
    PanelManagerModule.prototype = {
        builder: null,

        themes: [],

        optionButtonTooltips: {
            'sw-visibility': _.__('oro.cms.wysiwyg.option_panel.show_borders'),
            'preview': _.__('oro.cms.wysiwyg.option_panel.preview'),
            'fullscreen': _.__('oro.cms.wysiwyg.option_panel.fullscreen'),
            'export-template': _.__('oro.cms.wysiwyg.option_panel.export'),
            'undo': _.__('oro.cms.wysiwyg.option_panel.undo'),
            'redo': _.__('oro.cms.wysiwyg.option_panel.redo'),
            'gjs-open-import-webpage': _.__('oro.cms.wysiwyg.option_panel.import'),
            'canvas-clear': _.__('oro.cms.wysiwyg.option_panel.export')
        },

        /**
         * Run panels reformat
         * @initialize
         */
        init: function() {
            this._moveSettings();
            this._addOptionButtonTooltips();
            this.createThemeSelector();
        },

        createThemeSelector: function() {
            const pn = this.builder.Panels.getPanel('options');

            const themeSelector = new ThemeSelectorView({
                editor: this.builder,
                themes: this.themes
            });

            pn.view.$el.prepend(
                themeSelector.$el
            );
        },

        _addOptionButtonTooltips: function() {
            const pn = this.builder.Panels.getPanel('options');

            pn.buttons.each(function(button) {
                button.set('attributes', {
                    'data-toggle': 'tooltip',
                    'title': this.optionButtonTooltips[button.id]
                });
            }, this);

            $(pn.view.$el.find('[data-toggle="tooltip"]')).tooltip();
        },

        /**
         * Move settings tab to style manager above style property
         * @private
         */
        _moveSettings: function() {
            const $ = this.builder.$;

            const Panels = this.builder.Panels;

            const openTmBtn = Panels.getButton('views', 'open-tm');
            openTmBtn && openTmBtn.set('active', 1);
            const openSm = Panels.getButton('views', 'open-sm');
            openSm && openSm.set('active', 1);

            const traitsSector = $('<div class="gjs-sm-sector no-select">'+
                '<div class="gjs-sm-title"><span class="fa fa-cog"></span> Settings</div>' +
                '<div class="gjs-sm-properties" style="display: none;"></div></div>');
            const traitsProps = traitsSector.find('.gjs-sm-properties');
            traitsProps.append($('.gjs-trt-traits'));
            $('.gjs-sm-sectors').before(traitsSector);

            traitsSector.find('.gjs-sm-title').on('click', function() {
                const traitStyle = traitsProps.get(0).style;

                const hidden = traitStyle.display === 'none';
                if (hidden) {
                    traitStyle.display = 'block';
                } else {
                    traitStyle.display = 'none';
                }
            });

            Panels.removeButton('views', 'open-tm');

            this.builder.$('#gjs-clm-tags-field').on('click', '[data-tag-status]', function(e) {
                e.stopPropagation();
            });
        }
    };

    return PanelManagerModule;
});

