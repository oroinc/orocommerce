define(function(require) {
    'use strict';

    var RelatedDataComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var FormView = require('orob2bfrontend/js/app/views/form-view');

    /**
     * @export orob2border/js/app/components/related-data-component
     * @extends oroui.app.components.base.Component
     * @class orob2border.app.components.RelatedDataComponent
     */
    RelatedDataComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                account: 'input[name$="[account]"]',
                accountUser: 'input[name$="[accountUser]"]',
                website: 'select[name$="[website]"]'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.view = new FormView(this.options);

            this.options._sourceElement
                .on('change', this.options.selectors.accountUser, _.bind(this.onChangeAccountUser, this));

            mediator.on('entry-point:order:load', this.loadRelatedData, this);
        },

        onChangeAccountUser: function() {
            mediator.trigger('order:load:related-data');

            mediator.trigger('entry-point:order:trigger');
        },

        /**
         * @param {Object} response
         */
        loadRelatedData: function(response) {
            mediator.trigger('order:loaded:related-data', response);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('entry-point:order:load', this.loadRelatedData, this);

            this.options._sourceElement
                .off('change', this.options.selectors.accountUser, _.bind(this.onChangeAccountUser, this));

            RelatedDataComponent.__super__.dispose.call(this);
        }
    });

    return RelatedDataComponent;
});
