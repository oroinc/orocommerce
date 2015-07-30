/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var AddProductFromViewComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        mediator = require('oroui/js/mediator'),
        Error = require('oroui/js/error'),
        $ = require('jquery');

    AddProductFromViewComponent = BaseComponent.extend({
        initialize: function (options) {
            options._sourceElement.find('a').on('click', function (e) {
                e.preventDefault();
                var el = $(this);
                var form = $('.add-to-shopping-list-form');

                var validator;
                if (form.data('validator')) {
                    validator = form.validate();
                    console.log(validator);
                }

                $.ajax({
                    type: 'POST',
                    url: el.data('url'),
                    data: form.serialize(),
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
            });
        }
    });

    return AddProductFromViewComponent;
});
