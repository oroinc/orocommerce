import BaseClass from 'oroui/js/base-class';
import ThemeSelectorView from 'orocms/js/app/grapesjs/controls/theme-selector-view';
import settingsTemplate from 'tpl-loader!orocms/templates/grapesjs-settings.html';
import {getPanelButtonProps, isPanelConfigExist, getPanelProps} from './buttons-options';
import $ from 'jquery';
import _ from 'underscore';

/**
 * Create panel manager instance
 */
const PanelManagerModule = BaseClass.extend({
    builder: null,

    themes: [],

    settingsTemplate: settingsTemplate,

    /**
     * @inheritdoc
     */
    constructor: function PanelManagerModule(options) {
        PanelManagerModule.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        _.extend(this, _.pick(options, ['builder', 'themes']));

        if (!this.builder) {
            throw new Error('Required option builder not found.');
        }

        this.modifyPanels();
        this._moveSettings();
        this._addOptionButtonTooltips();
        this.createThemeSelector();

        this.listenTo(this.builder, 'component:selected', this.componentSelected.bind(this));
    },

    modifyPanels() {
        this.builder.Panels.getPanels().each(({buttons, id}) => {
            if (!isPanelConfigExist(id)) {
                return;
            }

            for (const [propName, propValue] of Object.entries(getPanelProps(id))) {
                buttons[propName] = propValue;
            }

            buttons.each(button => button.set(getPanelButtonProps(id, button.get('id'))));
            buttons.sort();
            buttons.reset(buttons.toJSON());
        });
    },

    createThemeSelector: function() {
        const pn = this.builder.Panels.getPanel('options');

        this.themeSelector = new ThemeSelectorView({
            editor: this.builder,
            themes: this.themes
        });

        pn.view.$el.prepend(this.themeSelector.$el);
    },

    _addOptionButtonTooltips: function() {
        const pn = this.builder.Panels.getPanel('options');

        pn.buttons.each(function(button) {
            button.set('attributes', {
                'data-toggle': 'tooltip'
            });
        }, this);

        $(pn.view.$el.find('[data-toggle="tooltip"]')).tooltip();
    },

    /**
     * Move settings tab to style manager above style property
     * @private
     */
    _moveSettings: function() {
        const Panels = this.builder.Panels;
        const builderEl = this.builder.editor.view.$el;

        const openTmBtn = Panels.getButton('views', 'open-tm');
        openTmBtn && openTmBtn.set('active', 1);
        const openSm = Panels.getButton('views', 'open-sm');
        openSm && openSm.set('active', 1);

        const traitsSector = $(this.settingsTemplate());
        const traitsProps = traitsSector.find('.gjs-sm-properties');
        $(Panels.getPanelsEl()).find('.gjs-sm-sectors').before(traitsSector);
        traitsProps.append(builderEl.find('.gjs-trt-traits'));

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

        builderEl.find('#gjs-clm-tags-field').on('click', '[data-tag-status]', function(e) {
            e.stopPropagation();
        });
    },

    componentSelected(model) {
        const builderEl = this.builder.editor.view.$el;

        $(builderEl.find('.gjs-settings'))
            .toggle(!!model.get('traits').length);
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.themeSelector.dispose();

        delete this.themeSelector;
        delete this.themes;
        delete this.builder;
        delete this.settingsTemplate;

        PanelManagerModule.__super__.dispose.call(this);
    }
});

export default PanelManagerModule;

