define(function(require) {
    'use strict';

    var AddButtonAbstractComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var ShoppingListWidget = require('orob2bshoppinglist/js/app/widget/shopping-list-widget');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var widgetManager = require('oroui/js/widget-manager');

    AddButtonAbstractComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            mediatorPrefix: '',
            intention: {
                create_new: 'new'
            },
            widgetAlias: 'shopping_list_add_product_widget'
        },

        /**
         * @property {jQuery.Element}
         */
        dialog: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            mediator.on(this.options.mediatorPrefix + ':add-widget-requested-response', this.showForm, this);

            this.options._sourceElement.on('click', '.grid-control', _.bind(this.onClick, this));
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
            var self = this;
            if (!this.dialog) {
                this.dialog = new ShoppingListWidget({});
                this.dialog.on('formSave', _.bind(function(response) {
                    self.reloadWidget(response);

                    this.selectShoppingList(response);
                    $('.btn[data-intention="current"]').data('id', response);
                }, this));
            }

            this.dialog.render();
        },

        /**
         * @param {String} id
         */
        reloadWidget: function(id) {
            if (this.buttonExists(id)) {
                return;
            }

            widgetManager.getWidgetInstanceByAlias(this.options.widgetAlias, function(widget) {
                widget.render();
            });
        },

        /**
         * @param {String} id
         */
        buttonExists: function(id) {
            return Boolean(this.options._sourceElement.find('[data-id="' + id + '"]').length);
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

            this.options._sourceElement.off();

            mediator.off(this.options.mediatorPrefix + ':add-widget-requested-response');

            AddButtonAbstractComponent.__super__.dispose.call(this);
        }
    });

    return AddButtonAbstractComponent;
});
