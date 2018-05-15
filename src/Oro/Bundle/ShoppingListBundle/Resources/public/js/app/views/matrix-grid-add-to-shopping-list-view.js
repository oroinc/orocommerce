define(function(require) {
    'use strict';

    var MatrixGridAddToShoppingListView;
    var ProductAddToShoppingListView = require('oroshoppinglist/js/app/views/product-add-to-shopping-list-view');
    var _ = require('underscore');

    MatrixGridAddToShoppingListView = ProductAddToShoppingListView.extend({
        /**
         * @inheritDoc
         */
        constructor: function MatrixGridAddToShoppingListView() {
            MatrixGridAddToShoppingListView.__super__.constructor.apply(this, arguments);
        },

        _saveLineItem: function(url, urlOptions, formData) {
            return this._addLineItem(url, urlOptions, formData);
        },

        _addLineItem: function(url, urlOptions, formData) {
            url = 'oro_shopping_list_frontend_matrix_grid_order';
            return MatrixGridAddToShoppingListView.__super__._addLineItem.call(this, url, urlOptions, formData);
        },

        validate: function(intention, url, urlOptions, formData) {
            if (!MatrixGridAddToShoppingListView.__super__.validate.call(this, url, urlOptions, formData)) {
                return false;
            }

            if (intention === 'update' || intention === 'remove' || this.options.emptyMatrixAllowed) {
                return true;
            }

            var isFormEmpty = _.every(this.$form.find('[data-name="field__quantity"]:enabled'), function(field) {
                return _.isEmpty(field.value);
            });

            if (isFormEmpty) {
                var validator = this.$form.validate();
                validator.errorsFor(this.$form[0]).remove();
                validator.showLabel(this.$form[0], _.__('oro.product.validation.configurable.required'));
                return false;
            }

            return true;
        }
    });

    return MatrixGridAddToShoppingListView;
});
