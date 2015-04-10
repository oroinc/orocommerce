/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var RequestStatusDeleteRestoreComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        mediator = require('oroui/js/mediator'),
        Error = require('oroui/js/error'),
        $ = require('jquery');

    RequestStatusDeleteRestoreComponent = BaseComponent.extend({
        initialize: function (options) {
            $('#' + options.id).on('click', function(e) {
                e.preventDefault();

                $.ajax({
                    url: options.url,
                    type: (options.action == 'delete') ? 'DELETE' : 'GET',
                    success: function(response) {
                         if (response && response.message) {
                             mediator.once('page:afterChange', function() {
                                mediator.execute('showFlashMessage', (response.successful ? 'success' : 'error'), response.message);
                             });
                         }
                        mediator.execute('refreshPage');
                    },
                    error: function(xhr, textStatus, error) {
                        Error.handle({}, xhr, {enforce: true});
                    }
                })
            });
        }
    });

    return RequestStatusDeleteRestoreComponent;
});