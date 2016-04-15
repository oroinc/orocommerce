require(['jquery', 'oroui/js/mediator', 'jquery.cookie'],
    function(jQuery, mediator, cookie) {
        (function($) {
            'use strict';
            var cookieName = 'order-tab:state';
            var $container = $('#container');

            $container.on('tab:toggle', '[data-tab-trigger]', function() {
                var $tab = $(this).closest('[data-tab]');
                mediator.trigger('scrollable-table:reload');

                if ($tab.hasClass('active')) {
                    $.cookie(cookieName, true, {path: window.location.pathname});
                } else {
                    $.cookie(cookieName, null, {path: window.location.pathname});
                }
            });

        })(jQuery);
    });
