define(function(require) {
    'use strict';

    var UpdateMatrixRowView;
    var BaseProductMatrixView = require('oropricing/js/app/views/base-product-matrix-view');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var _ = require('underscore');
    var $ = require('jquery');

    UpdateMatrixRowView = BaseProductMatrixView.extend({
        optionNames: BaseProductMatrixView.prototype.optionNames.concat(
            ['shoppingListId', 'productId']
        ),

        /**
         * Options for requires
         *
         * @property {Object}
         */
        options: {
            http_method: 'PUT',
            url: 'oro_shopping_list_frontend_matrix_grid_order'
        },

        /**
         * Extended elements
         *
         * @property {Object}
         */
        elements: _.extend({}, BaseProductMatrixView.prototype.elements, {
            updateButton: '[data-role="update-shopping-list"]',
            form: '[name="matrix_collection"]'
        }),

        /**
         * Extended element events
         *
         * @property {Object}
         */
        elementsEvents: _.extend({}, BaseProductMatrixView.prototype.elementsEvents, {
            updateButton: ['click', '_saveChanges']
        }),

        /**
         * @property {Number}
         */
        shoppingListId: null,

        /**
         * @property {Number}
         */
        productId: null,

        /**
         * Initialize component
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            UpdateMatrixRowView.__super__.initialize.apply(this, arguments);

            this.validator = this.getElement('form').validate();
        },

        isValid: function() {
            return this.validator.form();
        },

        /**
         * Send update quantity to shopping list
         *
         * @returns {boolean}
         * @private
         */
        _saveChanges: function() {
            if (!this.isValid()) {
                return false;
            }

            var formData = this.getElement('form').serialize();

            var urlOptions = {
                shoppingListId: this.shoppingListId,
                productId: this.productId
            };

            mediator.execute('showLoading');

            $.ajax({
                type: this.options.http_method,
                url: routing.generate(this.options.url, urlOptions),
                data: formData,
                success: function(response) {
                    mediator.trigger('shopping-list:line-items:update-response', {}, response);
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });
        }
    });

    return UpdateMatrixRowView;
});
