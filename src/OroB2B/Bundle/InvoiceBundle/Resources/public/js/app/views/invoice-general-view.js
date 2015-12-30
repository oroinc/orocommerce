define(function (require) {
    'use strict';

    var InvoiceGeneralView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');

    /**
     * @export orob2binvoice/js/app/views/invoice-general-view
     * @extends oroui.app.views.base.View
     * @class orob2invoice.app.views.InvoiceGeneralView
     */
    InvoiceGeneralView = BaseView.extend({
        /**
         * @inheritDoc
         */
        initialize: function (options) {
            options.el.on('change', '.invoice-currency select', function(){
                mediator.trigger('pricing:update-currency', $(this).val());
            });
        }
    });

    return InvoiceGeneralView;
});