/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var AddProductFromViewComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var ShoppingListWidgetComponent = require('orob2bshoppinglist/js/app/components/shopping-list-widget-component');
    var routing = require('routing');
    var mediator = require('oroui/js/mediator');
    var Error = require('oroui/js/error');
    var $ = require('jquery');
    var _ = require('underscore');
    var options = {
        intention: {
            new: 'new'
        }
    };

    AddProductFromViewComponent = BaseComponent.extend({
        initialize: function(additionalOptions) {
            var component = this;
            _.extend(options, additionalOptions || {});

            options._sourceElement.find('a').on('click', function() {
                var el = $(this);
                var form = el.closest('form');
                var url = el.data('url');
                var urlOptions = el.data('urloptions');
                var intention = el.data('intention');

                if (!component.validateForm(form)) {
                    return;
                }

                if (intention === options.intention.new) {
                    component.createNewShoppingList(url, urlOptions, form.serialize());
                } else {
                    component.addProductToShoppingList(url, urlOptions, form.serialize());
                }
            });
        },

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

        formElements: function(form) {
            return form.find('input, select, textarea').not(':submit, :reset, :image');
        },

        createNewShoppingList: function(url, urlOptions, formData) {
            var component = this;
            var dialog = ShoppingListWidgetComponent.createDialog();
            dialog.render();
            dialog.on('formSave', _.bind(function(response) {
                urlOptions.shoppingListId = response;
                component.addProductToShoppingList(url, urlOptions, formData);
            }, this));
        },

        addProductToShoppingList: function(url, urlOptions, formData) {
            $.ajax({
                type: 'POST',
                url: routing.generate(url, urlOptions),
                data: formData,
                success: function(response) {
                    if (response && response.message) {
                        mediator.once('page:afterChange', function() {
                            mediator.execute(
                                'showFlashMessage', (response.successful ? 'success' : 'error'),
                                response.message
                            );
                        });
                    }
                    mediator.execute('refreshPage');
                },
                error: function(xhr) {
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            options._sourceElement.off();
            ShoppingListWidgetComponent.dispose();

            AddProductFromViewComponent.__super__.dispose.call(this);
        }
    });

    return AddProductFromViewComponent;
});
