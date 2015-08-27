define(function(require) {
    'use strict';

    var FrontnedLineItemView;
    var LineItemAbstractView = require('orob2border/js/app/views/line-item-abstract-view');

    /**
     * @export orob2border/js/app/views/line-item-view
     * @extends oroui.app.views.base.View
     * @class orob2border.app.views.LineItemView
     */
    FrontnedLineItemView = LineItemAbstractView.extend({
        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            FrontnedLineItemView.__super__.handleLayoutInit.apply(this, arguments);

            this.subtotalFields([
                this.fieldsByName.product,
                this.fieldsByName.quantity,
                this.fieldsByName.productUnit
            ]);
        }
    });

    return FrontnedLineItemView;
});
