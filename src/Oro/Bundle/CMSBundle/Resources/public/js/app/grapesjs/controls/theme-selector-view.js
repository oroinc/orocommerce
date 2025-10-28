import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orocms/templates/grapesjs-dropdown-action.html';

const ThemeSelector = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'editor', 'themes'
    ]),

    autoRender: true,

    themes: [],

    template: template,

    className: 'gjs-select-control',

    currentTheme: null,

    events: {
        'click .dropdown-item': 'onClick',
        'input [name="theme-filter"]': 'onInput'
    },

    constructor: function ThemeSelector(options) {
        ThemeSelector.__super__.constructor.call(this, options);
    },

    initialize(options) {
        this.setCurrentTheme();

        ThemeSelector.__super__.initialize.call(this, options);
    },

    render() {
        const data = this.getTemplateData();
        const template = this.getTemplateFunction();
        const html = template(data);
        this.$el.html(html);

        this.$el.inputWidget('seekAndCreate');
    },

    getTemplateData() {
        const options = _.reduce(this.themes, function(options, theme) {
            options[theme.name] = theme.label;
            return options;
        }, {});

        return {
            currentTheme: this.currentTheme.label,
            options: options
        };
    },

    filterItems(str) {
        this.$el.find('[data-role="filterable-item"]').each(function(index, el) {
            $(el).toggle($(el).text().toLowerCase().indexOf(str.toLowerCase()) !== -1);
        });
    },

    setCurrentTheme(key) {
        if (key) {
            _.each(this.themes, function(theme) {
                theme.active = theme.name === key;
            }, this);
        }

        this.currentTheme = _.find(this.themes, function(theme) {
            return theme.active;
        });

        this.editor.em.set('currentTheme', this.currentTheme);
    },

    onClick(e) {
        const key = $(e.target).data('key');
        if (key === this.currentTheme) {
            return;
        }
        this.setCurrentTheme(key);

        this.editor.trigger('changeTheme', key);
        this.editor.em.trigger('changeTheme', key);

        this.render();
    },

    onInput(e) {
        this.filterItems(e.target.value);
    }
});

export default ThemeSelector;
