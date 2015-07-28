/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var AddProductComponent,
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        BaseComponent = require('oroui/js/app/components/base/component');

    AddProductComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            shoppingListSelector: '.orob2b-shoppinglist select',
            shoppingListLabelSelector: '.orob2b-shoppinglist-label'
        },

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @property {jQuery.Element}
         */
        shoppingListLabelSelector: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);
            this.loadingMaskView = new LoadingMaskView({container: this.options._sourceElement});

            this.shoppingListLabelSelector = this.options._sourceElement.find(this.options.shoppingListLabelSelector);
            this.options._sourceElement
                .on('change', this.options.shoppingListSelector, _.bind(this.onShoppingListChange, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onShoppingListChange: function (e) {
            var value = e.target.value;

            if (value == "") {
                this.shoppingListLabelSelector.parent('div').show();
            } else {
                this.shoppingListLabelSelector.parent('div').hide();
            }
        }
    });

    return AddProductComponent;
});
