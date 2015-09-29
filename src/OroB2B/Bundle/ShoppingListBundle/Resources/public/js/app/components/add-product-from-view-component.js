/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AddProductFromViewComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var Error = require('oroui/js/error');
    var $ = require('jquery');
    var _ = require('underscore');
    var widgetManager = require('oroui/js/widget-manager');

    AddProductFromViewComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            intention: {
                new: 'new'
            },
            widgetAlias: 'shopping_list_add_product_form'
        },

        /**
         * @property {jQuery.Element}
         */
        dialog: null,

        /**
         * @param {Object} additionalOptions
         */
        initialize: function(additionalOptions) {
            _.extend(this.options, additionalOptions || {});

            this.options._sourceElement.on('click', 'a[data-id]', _.bind(this.onClick, this));
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
            if (!this.dialog) {
                this.dialog = new ShoppingListWidget({});
                this.dialog.on('formSave', _.bind(function(response) {
                    urlOptions.shoppingListId = response;
                    self.addProductToShoppingList(url, urlOptions, formData);
                }, this));
            }

            this.dialog.render();
        },

        /**
         * @param {String} url
         * @param {Object} urlOptions
         * @param {Object} formData
         */
        addProductToShoppingList: function(url, urlOptions, formData) {
            var self = this;
            $.ajax({
                type: 'POST',
                url: routing.generate(url, urlOptions),
                data: formData,
                success: function(response) {
                    if (response && response.message) {
                        mediator.execute(
                            'showFlashMessage', (response.hasOwnProperty('successful') ? 'success' : 'error'),
                            response.message
                        );
                    }
                    if (!self.buttonExists(urlOptions.shoppingListId)) {
                        widgetManager.getWidgetInstanceByAlias(self.options.widgetAlias, function(widget) {
                            widget.render();
                        });
                    }
                },
                error: function(xhr) {
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        /**
         * @param {String} id
         */
        buttonExists: function(id) {
            return Boolean(this.options._sourceElement.find('[data-id="' + id + '"]').length);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            AddProductFromViewComponent.__super__.dispose.call(this);
        }
    });

    return AddProductFromViewComponent;
});
