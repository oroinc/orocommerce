/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AddProductComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    AddProductComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            shoppingListSelector: '.orob2b-shoppinglist select',
            shoppingListLabelSelector: '.orob2b-shoppinglist-label'
        },

        /**
         * @property {jQuery.Element}
         */
        shoppingListLabelSelector: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.shoppingListLabelSelector = this.options._sourceElement.find(this.options.shoppingListLabelSelector);
            this.options._sourceElement
                .on('change', this.options.shoppingListSelector, _.bind(this.onShoppingListChange, this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onShoppingListChange: function(e) {
            var value = e.target.value;

            if (value === '') {
                this.shoppingListLabelSelector.parent('div').removeClass('hidden');
            } else {
                this.shoppingListLabelSelector.parent('div').addClass('hidden');
            }
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            AddProductComponent.__super__.dispose.call(this);
        }
    });

    return AddProductComponent;
});
