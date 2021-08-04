define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const FormView = require('orofrontend/js/app/views/form-view');

    /**
     * @export oroorder/js/app/components/related-data-component
     * @extends oroui.app.components.base.Component
     * @class oroorder.app.components.RelatedDataComponent
     */
    const RelatedDataComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {},

        /**
         * @inheritdoc
         */
        constructor: function RelatedDataComponent(options) {
            RelatedDataComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
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
         * @inheritdoc
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
