define(function(require) {
    'use strict';

    var AddButtonAbstractComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');

    AddButtonAbstractComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            mediatorPrefix: '',
            intention: {
                create_new: 'new'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            mediator.on(this.options.mediatorPrefix + ':add-widget-requested-response', this.showForm, this);
            this.options._sourceElement.find('.grid-control').click(_.bind(this.onClick, this));
        },

        /**
         * @param {Event} e
         */
        onClick: function(e) {
            e.preventDefault();

            if ($(e.currentTarget).data('intention') === this.options.intention.create_new) {
                mediator.trigger(this.options.mediatorPrefix + ':add-widget-requested');
            } else {
                this.selectShoppingList($(e.currentTarget).data('id'));
            }
        },

        showForm: function() {
            var dialog = new ShoppingListWidget({});

            dialog.render();
            dialog.on('formSave', _.bind(function(response) {
                this.selectShoppingList(response);
                $('.btn[data-intention="current"]').data('id', response);
            }, this));
        },

        /**
         * @param {String} shoppingListId
         */
        selectShoppingList: function(shoppingListId) {
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.find('.grid-control').off();
            mediator.off(this.options.mediatorPrefix + ':add-widget-requested-response', this.showForm, this);
            AddButtonAbstractComponent.__super__.dispose.call(this);
        }
    });

    return AddButtonAbstractComponent;
});

