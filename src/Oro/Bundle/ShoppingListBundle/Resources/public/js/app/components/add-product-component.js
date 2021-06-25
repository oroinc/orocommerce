define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const AddProductComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            shoppingListSelector: '.oro-shoppinglist select',
            shoppingListLabelSelector: '.oro-shoppinglist-label'
        },

        /**
         * @property {jQuery.Element}
         */
        shoppingListLabelSelector: null,

        /**
         * @inheritdoc
         */
        constructor: function AddProductComponent(options) {
            AddProductComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.shoppingListLabelSelector = this.options._sourceElement.find(this.options.shoppingListLabelSelector);
            this.options._sourceElement
                .on('change', this.options.shoppingListSelector, this.onShoppingListChange.bind(this));
        },

        /**
         * @param {jQuery.Event} e
         */
        onShoppingListChange: function(e) {
            const value = e.target.value;

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
