define(function(require) {
    'use strict';

    const ViewComponent = require('oroui/js/app/components/view-component');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const $ = require('jquery');
    const {debounce} = require('underscore');
    const __ = require('orotranslation/js/translator');
    const ShoppingListCollectionService = require('oroshoppinglist/js/shoppinglist-collection-service');

    const ShoppingListWidgetViewComponent = ViewComponent.extend({
        shoppingListCollection: null,

        elements: {
            radio: '[data-role="set-default"]'
        },

        /**
         * @inheritdoc
         */
        constructor: function ShoppingListWidgetViewComponent(options) {
            ShoppingListWidgetViewComponent.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            this.$el = options._sourceElement;

            ShoppingListWidgetViewComponent.__super__.initialize.call(this, options);

            this.$el.on(`change.${this.cid}`,
                this.elements.radio,
                debounce(this._onCurrentShoppingListChange.bind(this), 300));
            this.$shoppingListWidget = this.$el.closest('.shopping-list-widget');

            this.bindShoppingListWidgetEvents();

            ShoppingListCollectionService.shoppingListCollection.done(collection => {
                this.shoppingListCollection = collection;
            });
        },

        bindShoppingListWidgetEvents: function() {
            this.$shoppingListWidget.on({
                [`mouseenter.${this.cid} click.${this.cid}`]: e => {
                    const $widget = $(e.currentTarget);

                    if (!$widget.hasClass('show')) {
                        $widget.find('[data-toggle="dropdown"]').dropdown('bindKeepFocusInside');
                        $widget.addClass('show-by-hover');
                    }
                },
                [`mouseleave.${this.cid} hidden.bs.dropdown.${this.cid}`]: e => {
                    const $widget = $(e.currentTarget);

                    if ($widget.hasClass('show') && !this.hasFocus($widget.find('.header-row__toggle'))) {
                        $widget.find('[data-toggle="dropdown"]').dropdown('unbindKeepFocusInside');
                    }

                    $widget.removeClass('show-by-hover');
                },
                [`focusin.${this.cid}`]: e => {
                    const $widget = $(e.currentTarget);

                    if (!$widget.hasClass('show') && this.hasFocus($widget.find('.header-row__toggle'))) {
                        $widget.attr('data-skip-focus-decoration-inner-elements', '');

                        setTimeout(() => {
                            $widget.removeClass('show-by-hover');
                            $widget.find('[data-toggle="dropdown"]').dropdown('toggle');
                            $widget.removeAttr('data-skip-focus-decoration-inner-elements');
                        }, 0);
                    }
                },
                [`focusout.${this.cid}`]: e => {
                    const $widget = $(e.currentTarget);

                    if (
                        $widget.hasClass('show') &&
                        e.relatedTarget &&
                        !$.contains(e.currentTarget, e.relatedTarget)
                    ) {
                        $widget.find('[data-toggle="dropdown"]').dropdown('toggle');
                    }
                }
            });
        },

        hasFocus: function(el) {
            return $.contains($(el)[0], document.activeElement);
        },

        /**
         * Change current shopping list event handler
         *
         * @param e
         */
        _onCurrentShoppingListChange: function(e) {
            const shoppingListId = parseInt($(e.target).val(), 10);
            const shoppingListLabel = $(e.target).data('label');

            $.ajax({
                method: 'PUT',
                url: routing.generate('oro_api_set_shopping_list_current', {
                    id: shoppingListId
                }),
                success: () => {
                    this.shoppingListCollection.each(function(model) {
                        model.set('is_current', model.get('id') === shoppingListId, {silent: true});
                    });
                    this.shoppingListCollection.trigger('change');

                    const message = __('oro.shoppinglist.actions.shopping_list_set_as_default', {
                        shoppingList: shoppingListLabel
                    });

                    mediator.execute('showFlashMessage', 'success', message, {namespace: 'shopping_list'});
                    mediator.trigger('layout-subtree:update:shopping_list_set_default');
                    mediator.trigger('layout-subtree:update:shopping_list_owner');
                }
            });
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$el.off(`.${this.cid}`);
            this.$shoppingListWidget.off(`.${this.cid}`);

            delete this.shoppingListCollection;
            delete this.$shoppingListWidget;

            ShoppingListWidgetViewComponent.__super__.dispose.call(this);
        }
    });

    return ShoppingListWidgetViewComponent;
});
