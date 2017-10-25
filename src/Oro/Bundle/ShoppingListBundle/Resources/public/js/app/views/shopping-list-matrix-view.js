define(function(require) {
    'use strict';

    var UpdateMatrixRowView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var mediator = require('oroui/js/mediator');
    var routing = require('routing');
    var _ = require('underscore');
    var $ = require('jquery');

    UpdateMatrixRowView = BaseView.extend(_.extend({}, ElementsHelper, {
        /**
         * @inheritDoc
         */
        optionNames: BaseView.prototype.optionNames.concat(
            ['shoppingListId', 'productId']
        ),

        /**
         * @inheritDoc
         */
        options: {
            http_method: 'POST',
            url: 'oro_shopping_list_frontend_matrix_grid_order'
        },

        /**
         * @inheritDoc
         */
        elements: _.extend({}, BaseView.prototype.elements, {
            updateButton: ['form', 'button[type="submit"]'],
            form: '[name="matrix_collection"]'
        }),

        /**
         * @inheritDoc
         */
        elementsEvents: _.extend({}, BaseView.prototype.elementsEvents, {
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
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.extend({}, this.options, options);
            UpdateMatrixRowView.__super__.initialize.apply(this, arguments);

            this.initializeElements(options);

            this.validator = this.getElement('form').validate();
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.disposeElements();
            UpdateMatrixRowView.__super__.dispose.apply(this, arguments);
        },

        isValid: function() {
            return this.validator.form();
        },

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
                    mediator.trigger('shopping-list:line-items:update-response', {}, {});
                },
                complete: function() {
                    mediator.execute('hideLoading');
                }
            });

            return false;
        }
    }));

    return UpdateMatrixRowView;
});
