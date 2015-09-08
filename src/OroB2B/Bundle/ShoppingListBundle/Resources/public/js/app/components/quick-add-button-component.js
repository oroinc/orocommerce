define(function(require) {
    'use strict';

    var QuickAddButtonComponent;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var AddButtonAbstractComponent = require('orob2bshoppinglist/js/app/components/add-button-abstract-component');

    QuickAddButtonComponent = AddButtonAbstractComponent.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, {
                mediatorPrefix: 'shoppinglist:quick-add'
            });

            QuickAddButtonComponent.__super__.initialize.apply(this, arguments);

            mediator.on(this.options.mediatorPrefix + ':add-widget-requested', this.triggerWidget, this);
        },

        /**
         * @inheritDoc
         */
        selectShoppingList: function(shoppingListId) {
            QuickAddButtonComponent.__super__.selectShoppingList.apply(this, arguments);
            mediator.trigger('quick-add:submit', 'orob2b_shopping_list_quick_add_processor', shoppingListId);
        },

        triggerWidget: function() {
            mediator.trigger(this.options.mediatorPrefix + ':add-widget-requested-response');
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off(this.options.mediatorPrefix + ':add-widget-requested', this.triggerWidget, this);
            QuickAddButtonComponent.__super__.dispose.call(this);
        }
    });

    return QuickAddButtonComponent;
});

