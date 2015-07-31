/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var AddProductFromViewComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        DialogWidget = require('oro/dialog-widget'),
        routing = require('routing'),
        mediator = require('oroui/js/mediator'),
        Error = require('oroui/js/error'),
        $ = require('jquery');

    AddProductFromViewComponent = BaseComponent.extend({
        initialize: function (options) {
            var component = this;
            options._sourceElement.on('click', function () {
                var el = $(this),
                    form = el.closest('form');

                if (!component._validate(form)) {
                    return;
                }

                component.addProductToShoppingList(el.data('url'), form.serialize());
            });
        },

        _validate: function (form) {
            var component = this,
                validator,
                valid = true;

            if (form.data('validator')) {
                validator = form.validate();
                $.each(component._elements(form), function () {
                    valid = validator.element(this) && valid;
                });
            }

            return valid;
        },

        _elements: function (form) {
            return form.find('input, select, textarea').not(':submit, :reset, :image');
        },

        createNewShoppingList: function () {
            var component = this,
                dialog = new DialogWidget({
                    'url': routing.generate('orob2b_shopping_list_frontend_create'),
                    'title': 'Create new Shopping List',
                    'regionEnabled': false,
                    'incrementalPosition': false,
                    'dialogOptions': {
                        'modal': true,
                        'resizable': false,
                        'width': '675',
                        'autoResize': true
                    }
                });
            dialog.render();
            dialog.on('formSave', _.bind(function (response) {
                mediator.trigger('frontend:shoppinglist:products-add', {id: response});
            }, this));
        },

        addProductToShoppingList: function (url, formData) {
            $.ajax({
                type: 'POST',
                url: url,
                data: formData,
                success: function (response) {
                    if (response && response.message) {
                        mediator.once('page:afterChange', function () {
                            mediator.execute('showFlashMessage', (response.successful ? 'success' : 'error'), response.message);
                        });
                    }
                    mediator.execute('refreshPage');
                },
                error: function (xhr) {
                    Error.handle({}, xhr, {enforce: true});
                }
            });
        }
    });

    return AddProductFromViewComponent;
});
