/** @lends ProductQuantityEditableComponent */
define(function(require) {
    'use strict';

    var ProductQuantityEditableComponent;
    var InlineEditableViewComponent = require('oroform/js/app/components/inline-editable-view-component');
    var $ = require('jquery');
    var _ = require('underscore');

    ProductQuantityEditableComponent = InlineEditableViewComponent.extend(/** @exports ProductQuantityEditableComponent.prototype */{
        options: {

        },

        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.options, options);

            this.messages = options.messages;
            this.metadata = options.metadata;

            this.inlineEditingOptions = options.metadata.inline_editing;

            this.dataKey = options.dataKey;
            this.quantityFieldName = options.quantityFieldName;
            this.unitFieldName = options.unitFieldName;

            this.$el = options._sourceElement;
        },


    });

    return ProductQuantityEditableComponent;
});
