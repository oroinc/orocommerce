define(function(require) {
    'use strict';

    var RelatedDataComponent;
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
            relatedDataRoute: '',
            formName: '',
            selectors: {
                account: 'input[name$="[account]"]',
                website: 'select[name$="[website]"]'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.view = new FormView(this.options);

            mediator.on('entry-point:order:load', this.loadRelatedData, this);
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

            RelatedDataComponent.__super__.dispose.call(this);
        }
    });

    return RelatedDataComponent;
});
