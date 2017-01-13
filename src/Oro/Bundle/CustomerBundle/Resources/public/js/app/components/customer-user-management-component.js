/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var CustomerUserManagementComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        mediator = require('oroui/js/mediator'),
        $ = require('jquery');

    CustomerUserManagementComponent = BaseComponent.extend({
        initialize: function (options) {
            options._sourceElement.find('a').on('click', function(e) {
                e.preventDefault();
                var el = $(this);
                $.ajax({
                    url: el.prop('href'),
                    type: 'GET',
                    success: function(response) {
                        if (response && response.message) {
                            mediator.once('page:afterChange', function() {
                                mediator.execute('showFlashMessage', (response.successful ? 'success' : 'error'), response.message);
                            });
                        }
                        mediator.execute('refreshPage');
                    }
                });
            });
        }
    });

    return CustomerUserManagementComponent;
});
