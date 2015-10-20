define(function(require) {
    'use strict';

    var ThemeSelectView;
    var BaseView = require('oroui/js/app/views/base/view');
    var _ = require('underscore');

    ThemeSelectView = BaseView.extend({
        /**
         * @property {String}
         */
        template: '<span class="theme-description"><%= description %></span>',

        /**
         * @property {Object}
         */
        options: {
            descriptionContainer: '.description-container',
            selectSelector: 'select',
            metadata: {}
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            this.$selector = this.$el.find(this.options.selectSelector);
            this.$descriptionContainer = this.$el.find(this.options.descriptionContainer);

            this.delegate('change', this.options.selectSelector, this.render);

            this.render();
        },

        /**
         * @inheritDoc
         */
        getTemplateData: function() {
            var selectedTheme = this.$selector.val();

            if (_.has(this.options.metadata, selectedTheme)) {
                return this.options.metadata[selectedTheme];
            }

            return false;
        },

        /**
         * @inheritDoc
         */
        render: function() {
            var templateFunction = this.getTemplateFunction();
            var data = this.getTemplateData();

            if (data !== false) {
                this.$descriptionContainer.html(
                    templateFunction(data)
                );
                this.$descriptionContainer.show();
            } else {
                this.$descriptionContainer.hide();
            }
        }
    });

    return ThemeSelectView;
});
