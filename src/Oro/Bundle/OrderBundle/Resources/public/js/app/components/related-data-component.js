define(function(require) {
    'use strict';

    var RelatedDataComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var FormView = require('orofrontend/js/app/views/form-view');

    /**
     * @export oroorder/js/app/components/related-data-component
     * @extends oroui.app.components.base.Component
     * @class oroorder.app.components.RelatedDataComponent
     */
    RelatedDataComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.view = new FormView(this.options);

            mediator.on('customer-customer-user:change', this.onChangeCustomerUser, this);
            mediator.on('entry-point:order:load', this.loadRelatedData, this);
        },

        onChangeCustomerUser: function() {
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

            mediator.off('customer-customer-user:change', this.onChangeCustomerUser, this);
            mediator.off('entry-point:order:load', this.loadRelatedData, this);

            RelatedDataComponent.__super__.dispose.call(this);
        }
    });

    return RelatedDataComponent;
});
