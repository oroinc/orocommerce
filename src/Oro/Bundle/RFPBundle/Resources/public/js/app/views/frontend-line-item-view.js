define(function(require) {
    'use strict';

    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const BaseView = require('oroui/js/app/views/base/view');
    const NumberFormatter = require('orolocale/js/formatter/number');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');
    const _ = require('underscore');

    const FrontendLineItemView = BaseView.extend(_.extend({}, ElementsHelper, {
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
            fieldCurrency: '[data-name="field__currency"]',
            fieldCommentCheckbox: '[data-role="field__comment-checkbox"]',
            fieldComment: '[data-name="field__comment"]',
            remove: '[data-role="remove"]'
        },

        elementsEvents: {
            edit: ['click', 'edit'],
            update: ['click', 'update'],
            decline: ['click', 'decline']
        },

        formState: null,

        events: {
            'content:remove [data-role="lineitem"]': 'onSubCollectionItemRemove'
        },

        /**
         * @inheritdoc
         */
        constructor: function FrontendLineItemView(options) {
            FrontendLineItemView.__super__.constructor.call(this, options);
        },

        /**
         * @param {Object} options
         */
        initialize: function(options) {
            FrontendLineItemView.__super__.initialize.call(this, options);
            this.initializeElements(options);
            this.onInit();
            this.template = _.template(this.getElement('template').text());

            this.listenTo(mediator, 'line-items:show:before', this.onShowBefore);

            if (_.isEmpty(this.getProduct())) {
                this.createInputWidget();
            }
        },

        onInit: function() {
            if (!_.isEmpty(this.formState)) {
                return;
            }
            this.getElement('decline').text(_.__('oro.rfp.request.btn.delete.label'));
        },

        onShowBefore: function() {
            if (!_.isEmpty(this.getProduct())) {
                this.viewMode();
            }
        },

        createInputWidget: function() {
            this.$el.removeAttr('data-skip-input-widgets').inputWidget('seekAndCreate');
        },

        render: function() {
            const viewContent = this.template(this.getData());
            this.getElement('viewContent').html(viewContent);
        },

        toggleEditMode: function(key) {
            if (key === 'enable') {
                this.getElement('viewView').addClass('hidden');
                this.getElement('editView').removeClass('hidden');
                this.getElement('decline').text(_.__('oro.rfp.request.btn.cancel.label'));
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
            this.createInputWidget();
            this.toggleEditMode('enable');
        },

        edit: function(e) {
            e.preventDefault();
            this.saveFormState();
            this.editMode();
        },

        decline: function(e) {
            e.preventDefault();
            if (_.isEmpty(this.formState)) {
                this.remove();
            } else {
                this.revertChanges();
                this.viewMode();
            }
        },

        update: function(e) {
            e.preventDefault();
            this.viewMode();
        },

        remove: function() {
            this.getElement('remove').click();
        },

        onSubCollectionItemRemove(e) {
            if ($(e.target).siblings(e.target).length === 0) {
                // gives time to remove last item of sub-collection before removing product line item
                _.delay(this.remove.bind(this));
            }
        },

        getData: function() {
            this.clearElementsCache();
            const data = {
                formatter: NumberFormatter
            };

            const $quantities = this.getElement('fieldQuantity');
            const $units = this.getElement('fieldUnit');
            const $prices = this.getElement('fieldPrice');
            const $currencies = this.getElement('fieldCurrency');

            data.product = this.getProduct();
            data.comment = this.getComment() || '';
            data.lines = [];

            _.each($quantities, function(quantity, i) {
                data.lines.push({
                    quantity: $quantities[i].value,
                    unit: $units[i].value,
                    price: NumberFormatter.unformatStrict($prices[i].value),
                    currency: $currencies[i].value,
                    found_price: $($prices[i]).data('found_price')
                });
            });

            return data;
        },

        getComment: function() {
            const commentChecked = this.getElement('fieldCommentCheckbox').prop('checked');

            if (!commentChecked) {
                this.getElement('fieldComment').val('');
                return null;
            }

            return this.getElement('fieldComment').val();
        },

        getProduct: function() {
            const $fieldProduct = this.getElement('fieldProduct');
            const selectedData = $fieldProduct.data('selected-data') || {};
            return $fieldProduct.inputWidget('data') || selectedData || null;
        },

        validate: function() {
            let isValid = !_.isEmpty(this.getProduct());
            const validator = this.$el.closest('form').validate();
            if (!isValid && validator) {
                validator.showLabel(this.getElement('fieldProduct')[0], _.__('oro.rfp.requestproduct.product.blank'));
                return isValid;
            }

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
            this.$el.find(':input[data-name]').each((i, el) => {
                this.formState[el.name] = el.value;
            });
        },

        revertChanges: function() {
            if (!this.formState) {
                return;
            }
            this.$el.find(':input[data-name]').each((i, el) => {
                const value = this.formState[el.name];
                if (value !== undefined && el.value !== value) {
                    el.value = value;
                    $(el).change();
                }
            });
        }
    }));

    return FrontendLineItemView;
});
