require(['jquery', 'oroui/js/mediator'], function(
    jQuery,
    mediator
) {
    (function($) {
        'use strict';
        $('.checkout-order-summary__header [data-tab-trigger]').on('tab:triggered', function() {
            mediator.trigger('scrollable-table:reload');
        })
    })(jQuery);
});
