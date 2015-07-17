/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var RequestStatusDeleteRestoreComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var  mediator = require('oroui/js/mediator');
    var Error = require('oroui/js/error');
    var  $ = require('jquery');

    RequestStatusDeleteRestoreComponent = BaseComponent.extend({
        initialize: function(options) {
            $('#' + options.id).on('click', function(e) {
                e.preventDefault();

                $.ajax({
                    url: options.url,
                    type: (options.action == 'delete') ? 'DELETE' : 'GET',
                    success: function(response) {
                        if (response && response.message) {
                            mediator.once('page:afterChange', function() {
                                var status = response.successful ? 'success' : 'error';
                                mediator.execute('showFlashMessage', status, response.message);
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
