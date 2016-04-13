require(['jquery', 'oroui/js/mediator', 'jquery.cookie'],
    function(jQuery, mediator, cookie) {
        (function($) {
            'use strict';
            var $orderSummaryTab = $('.checkout-order-summary__tab');
            var $orderSummaryTabTrigger = $('.checkout-order-summary__header [data-tab-trigger]');
            var orderTabStateCookieName = 'order-tab:state';

            $orderSummaryTabTrigger.on('tab:triggered', function() {
                mediator.trigger('scrollable-table:reload');

                if ($orderSummaryTab.hasClass('active')) {
                    $.cookie(orderTabStateCookieName, true, {path: window.location.pathname});
                } else {
                    $.cookie(orderTabStateCookieName, null, {path: window.location.pathname});
                }
            });

            if ($.cookie(orderTabStateCookieName)) {
                $orderSummaryTab.addClass('active');
            } else {
                $orderSummaryTab.removeClass('active');
            }
        })(jQuery);
    });
