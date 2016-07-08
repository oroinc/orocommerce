define(function(require) {
    'use strict';

    var BaseShoppingListsLinkView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var WidgetComponent = require('oroui/js/app/components/widget-component');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    BaseShoppingListsLinkView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            elements: {
                container: '[data-shopping-lists]',
                dataEditSingleShoppingList: '[data-edit-single-shopping-list]',
                editMultipleShoppingList: '[data-edit-multiple-shopping-list]'
            },
            templates: '',
            widgetOptions: ''
        },

        demoData: {
            shopping_lists: [
                {
                    shopping_list_id: 0,
                    shopping_list_label: 'Shopping List 1',
                    is_current: true,
                    line_items: [
                        {
                            unit: 'item',
                            quantity: 5
                        },
                        {
                            unit: 'set',
                            quantity: 1
                        }
                    ]
                },
                {
                    shopping_list_id: 1,
                    shopping_list_lable: 'Shopping List 2',
                    line_items: [
                        {
                            unit: 'item',
                            quantity: 10
                        }
                    ]
                }
            ]
        },
        
        initialize: function(options) {
            BaseShoppingListsLinkView.__super__.initialize.apply(this, arguments);
            this.options = _.defaults(options || {}, this.options);
            this.widgetOptions = this.options.widgetOptions;
            this.template = _.template(options.template);

            this.initModel(options);
            if (!this.model) {
                return;
            }
            this.initializeElements(options);

            this.model.on('change:shopping_lists', this.updateShoppingLists, this);

            this.render();
        },

        dispose: function() {
            this.disposeElements();
            BaseShoppingListsLinkView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.updateShoppingLists();
            this.attachEvents();
        },

        initModel: function(options) {
            this.demoData = $.extend(true, {}, this.demoData, options.demoData || {});
            if (options.productModel) {
                this.model = options.productModel;
            }
            _.each(this.demoData, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        setLabels: function(currentShoppingList) {
            if (!currentShoppingList) {
                return null;
            }

            var currentShoppingListLabel = currentShoppingList.shopping_list_label;

            if (_.has(currentShoppingList, 'line_items')) {
                _.each(currentShoppingList.line_items, function (lineItem) {
                    var lineItemsLabel = _.__(
                        'orob2b.product.product_unit.' + lineItem.unit + '.value.short',
                        {'count': lineItem.quantity},
                        lineItem.quantity);

                    lineItem.name = _.__('orob2b.shoppinglist.actions.added_to_shopping_list')
                        .replace('{{ lineItems }}', lineItemsLabel);

                    lineItem.name = lineItem.name.replace('{{ shoppingList }}', currentShoppingListLabel);
                });
            }
        },

        findCurrentShoppingList: function(shoppingLists) {
            if (!shoppingLists || !_.isObject(shoppingLists)) {
                return null;
            }
            return _.find(shoppingLists, function(list) {
                return list.is_current;
            }) || null;
        },

        updateShoppingLists: function() {
            var data = {};

            if (!this.model) {
                return;
            }

            var shoppingLists = this.model.get('shopping_lists');

            this.setLabels(this.findCurrentShoppingList(shoppingLists));
            data.shoppingList = this.findCurrentShoppingList(shoppingLists);
            data.shoppingLists = shoppingLists;

            this.renderShoppingLists(data);
        },

        attachEvents: function() {
            this.delegateElementEvent('editMultipleShoppingList', 'click', _.bind(this.renderShoppingListsPopup, this));
            this.delegateElementEvent('dataEditSingleShoppingList', 'click', _.bind(_.debounce(this.updateLineItem, 50), this));
        },

        renderShoppingListsPopup: function() {
            if (!this.widgetComponent) {
                this.widgetComponent = new WidgetComponent(this.widgetOptions);
            }
            this.widgetComponent.openWidget();
        },

        updateLineItem: function(event) {
            var $this = $(event.currentTarget);

            this.model.set({
                'quantity': $this.data('quantity'),
                'unit': $this.data('unit')
            });
        },

        renderShoppingLists: function(data) {
            this.getElement('container').html(this.template({data: data}));
        }
    }));

    return BaseShoppingListsLinkView;
});
