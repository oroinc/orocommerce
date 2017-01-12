define(function(require) {
    'use strict';

    var FrontendLineItemView;
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var BaseView = require('oroui/js/app/views/base/view');
    var NumberFormatter = require('orolocale/js/formatter/number');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    FrontendLineItemView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            lineItem: '[data-role="line-item"]',
            editView: '[data-role="line-item-edit"]',
            viewView: '[data-role="line-item-view"]',
            template: ['$html', '#rfp-form-line-item-view-template'],
            edit: '[data-role="edit"]',
            update: '[data-role="update"]',
            decline: '[data-role="decline"]',
            viewContent: ['viewView', '[data-role="content"]'],
            fieldProduct: '[data-name="field__product"]',
            fieldQuantity: '[data-name="field__quantity"]',
            fieldUnit: '[data-name="field__product-unit"]',
            fieldPrice: '[data-name="field__value"]',
            fieldComment: '[data-name="field__comment"]'
        },

        elementsEvents: {
            edit: ['click', 'edit'],
            update: ['click', 'update'],
            decline: ['click', 'decline']
        },

        formState: null,

        initialize: function(options) {
            FrontendLineItemView.__super__.initialize.apply(this, arguments);
            this.initializeElements(options);
            this.template = _.template(this.getElement('template').text());

            this.listenTo(mediator, 'line-items:show:before', this.viewMode);
        },

        render: function() {
            var viewContent = this.template(this.getData());
            this.getElement('viewContent').html(viewContent);
        },

        toggleEditMode: function(key) {
            if (key === 'enable') {
                this.getElement('viewView').addClass('hidden');
                this.getElement('editView').removeClass('hidden');
            } else {
                this.getElement('editView').addClass('hidden');
                this.getElement('viewView').removeClass('hidden');
            }
        },

        viewMode: function() {
            if (!this.validate()) {
                return;
            }
            this.render();
            this.toggleEditMode('disable');
        },

        editMode: function() {
            this.$el.removeAttr('data-skip-input-widgets').inputWidget('seekAndCreate');
            this.toggleEditMode('enable');
        },

        edit: function(e) {
            e.preventDefault();
            this.saveFormState();
            this.editMode();
        },

        decline: function(e) {
            e.preventDefault();
            this.revertChanges();
            this.viewMode();
        },

        update: function(e) {
            e.preventDefault();
            this.viewMode();
        },

        getData: function() {
            this.clearElementsCache();
            var data = {
                formatter: NumberFormatter
            };

            var $quantities = this.getElement('fieldQuantity');
            var $units = this.getElement('fieldUnit');
            var $prices = this.getElement('fieldPrice');

            data.product = this.getProduct();
            data.comment = this.getElement('fieldComment').val();
            data.lines = [];

            _.each($quantities, function(quantity, i) {
                data.lines.push({
                    quantity: $quantities[i].value,
                    unit: $units[i].value,
                    price: $prices[i].value,
                    found_price: $($prices[i]).data('found_price')
                });
            });

            return data;
        },

        getProduct: function() {
            var $fieldProduct = this.getElement('fieldProduct');
            var selectedData = $fieldProduct.data('selectedData') || [];
            return $fieldProduct.inputWidget('data') || selectedData[0] || null;
        },

        validate: function() {
            var isValid = !_.isEmpty(this.getProduct());
            var validator = this.$el.closest('form').validate();

            if (validator) {
                this.$el.find(':input').each(function() {
                    if ($(this).data('name')) {
                        isValid = validator.element(this) && isValid;
                    }
                });
            }
            return isValid;
        },

        saveFormState: function() {
            this.formState = {};
            this.$el.find(':input[data-name]').each(_.bind(function(i, el) {
                this.formState[el.name] = el.value;
            }, this));
        },

        revertChanges: function() {
            this.$el.find(':input[data-name]').each(_.bind(function(i, el) {
                var value = this.formState[el.name];
                if (value !== undefined && el.value !== value) {
                    el.value = value;
                    $(el).change();
                }
            }, this));
        }
    }));

    return FrontendLineItemView;
});
