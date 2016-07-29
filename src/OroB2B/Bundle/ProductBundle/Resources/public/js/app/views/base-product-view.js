define(function(require) {
    'use strict';

    var BaseProductView;
    var BaseView = require('oroui/js/app/views/base/view');
    var ElementsHelper = require('orob2bfrontend/js/app/elements-helper');
    var BaseModel = require('oroui/js/app/models/base/model');
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    BaseProductView = BaseView.extend(_.extend({}, ElementsHelper, {
        options: {
            offset: 20,
            elInRow: 5
        },

        elements: {
            quantity: '[data-name="field__quantity"]',
            unit: '[data-name="field__unit"]',
            footer: '[data-product-footer]',
            shoppingLists: '[data-product-shopping-lists]'
        },

        modelElements: {
            quantity: 'quantity',
            unit: 'unit'
        },

        modelAttr: {
            id: 0,
            quantity: 0,
            unit: ''
        },

        initialize: function(options) {
            this.options = _.extend(this.options, options || {});
            BaseProductView.__super__.initialize.apply(this, arguments);

            this.initModel(this.options);
            this.initializeElements(this.options);
            this.initLayout({
                productModel: this.model
            });

            this.model.on('change:quantity', this.updateQuantity, this);

            this.render();
        },

        updateQuantity: function() {
            $(this.elements.quantity, this.$elem).val(this.model.get('quantity'));
        },

        initModel: function(options) {
            this.$elem = options.el;
            this.modelAttr = $.extend(true, {}, this.modelAttr, options.modelAttr || {});
            if (options.productModel) {
                this.model = options.productModel;
            }
            if (!this.model) {
                this.model = new BaseModel();
            }

            _.each(this.modelAttr, function(value, attribute) {
                if (!this.model.has(attribute)) {
                    this.model.set(attribute, value);
                }
            }, this);
        },

        dispose: function() {
            delete this.modelAttr;
            delete this.$conteiner;
            delete this.$conteinerіs;
            delete this.$currentFooter;
            delete this.$footers;
            delete this.$shoppingLists;
            this.disposeElements();
            this.model.off('change:quantity', this.updateQuantity, this);
            this.model.off('editLineItem', this.alignFooter, this);
            mediator.off(null, null, this);
            BaseProductView.__super__.dispose.apply(this, arguments);
        },

        render: function() {
            this.$conteiner = this.$el.parent();
            this.$conteinerіs = this.$conteiner.add(this.$conteiner.siblings());
            this.$currentFooter = $(this.elements.footer, this.$el);
            this.$footers = $(this.elements.footer);
            this.$shoppingLists = $(this.elements.shoppingLists);

            if (this.$currentFooter.data('theme') === 'gallery-view') {
                this.model.on('editLineItem', this.alignFooter);
                mediator.on('shopping-list-event:update', _.bind(this.alignFooter, this));
                mediator.on('page:afterChange', _.bind(this.alignFooter, this));
            }
        },

        alignFooter: function() {
            var self = this;

            this.$conteinerіs.each(function(index) {
                var $this = $(this);
                var $currentShoppingLists = $this.find(self.elements.shoppingLists);
                var startPos = Math.floor(index / self.options.elInRow, 0) * self.options.elInRow;
                var endPos = startPos + self.options.elInRow;
                var $footersListsRow = self.$footers.slice(startPos, endPos);
                var $shoppingListsRow = self.$shoppingLists.slice(startPos, endPos);
                var maxFooterHeight = self._getMaxHeight($footersListsRow);
                var maxShippingListsHeight = self._getMaxHeight($shoppingListsRow);
                var shoppingListHeight = $currentShoppingLists.outerHeight();
                var offset = (maxFooterHeight + self.options.offset) - (maxShippingListsHeight - shoppingListHeight);

                $this.css({
                    'margin-bottom': offset
                });
            });
        },

        _getMaxHeight: function($collections) {
            var heights = $collections.map(function() {
                return $(this).outerHeight();
            }).get();

            return Math.max.apply(null, heights);
        }
    }));

    return BaseProductView;
});
