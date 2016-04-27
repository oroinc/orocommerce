define(function(require) {
    'use strict';

    var AddProductView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var Error = require('oroui/js/error');
    var $ = require('jquery');
    var _ = require('underscore');

    AddProductView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            intention: {
                new: 'new'
            },
            widgetAlias: 'shopping_list_add_product_form',
            createNewLabel: 'orob2b.shoppinglist.widget.add_to_new_shopping_list',
            addToShoppingListButtonSelector: '.add-to-shopping-list-button'
        },

        /**
         * @property {jQuery.Element}
         */
        dialog: null,

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            AddProductView.__super__.initialize.apply(this, arguments);

            _.extend(this.options, options || {});
            this.$el.on('click', _.bind(this.onClick, this));

            var product = this.$el.data('product');
            this.$el.find('.pinned-dropdown').data('product', product);
            this.initLayout();
        },

        /**
         * @param {jQuery.Event} e
         */
        onClick: function(e) {
            var el = $(e.target);
            var form = el.closest('form');
            var url = el.data('url');
            var urlOptions = el.data('urloptions');
            var intention = el.data('intention');

            if (!this.validateForm(form)) {
                return;
            }

            if (intention === this.options.intention.new) {
                this.createNewShoppingList(url, urlOptions, form.serialize());
            } else {
                this.addProductToShoppingList(url, urlOptions, form.serialize());
            }
        },

        /**
         * @param {Object} form
         */
        validateForm: function(form) {
            var component = this;
            var validator;
            var valid = true;

            if (form.data('validator')) {
                validator = form.validate();
                $.each(component.formElements(form), function() {
                    valid = validator.element(this) && valid;
                });
            }

            return valid;
        },

        /**
         * @param {Object} form
         */
        formElements: function(form) {
            return form.find('input, select, textarea').not(':submit, :reset, :image');
        },

        /**
         * @param {String} url
         * @param {Object} urlOptions
         * @param {Object} formData
         */
        createNewShoppingList: function(url, urlOptions, formData) {
            var self = this;

            this.dialog = new ShoppingListWidget({});
            this.dialog.on('formSave', _.bind(function(response) {
                urlOptions.shoppingListId = response;
                self.addProductToShoppingList(url, urlOptions, formData);
            }, this));

            this.dialog.render();
        },

        /**
         * @param {String} url
         * @param {Object} urlOptions
         * @param {Object} formData
         */
        addProductToShoppingList: function(url, urlOptions, formData) {
            var self = this;
            mediator.execute('showLoading');
            $.ajax({
                type: 'POST',
                url: routing.generate(url, urlOptions),
                data: formData,
                success: function(response) {
                    mediator.execute('hideLoading');
                    if (response && response.message) {
                        mediator.execute(
                            'showFlashMessage', (response.hasOwnProperty('successful') ? 'success' : 'error'),
                            response.message
                        );
                    }
                    if (!self.buttonExists(urlOptions.shoppingListId)) {
                        mediator.trigger('shopping-list:created', response.shoppingList, response.product);
                    } else {
                        mediator.trigger('shopping-list:updated', response.shoppingList, response.product);
                    }
                },
                error: function(xhr) {
                    mediator.execute('hideLoading');
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        /**
         * @param {String} id
         */
        buttonExists: function(id) {
            return Boolean(this.$el.find('[data-id="' + id + '"]').length);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off();

            AddProductView.__super__.dispose.call(this);
        }
    });

    return AddProductView;
});
