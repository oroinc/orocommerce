/* global require */
require([
    'jquery',
    'oroui/js/mediator',
    'oroui/js/error'
], function ($, mediator, Error) {
    'use strict';
    $(function () {
        $(document).on('click', '.add-to-shopping-list-btn', function () {
            var btn = $(this);
            $.ajax({
                type: 'POST',
                url: btn.data('url'),
                data: $('.add-to-shopping-list-form').serialize(),
                success: function (response) {
                    console.log(response);
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
    });
});
