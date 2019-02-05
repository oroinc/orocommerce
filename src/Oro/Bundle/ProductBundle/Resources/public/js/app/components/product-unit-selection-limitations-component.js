define(function(require) {
    'use strict';

    var ProductUnitSelectionLimitationsComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');

    ProductUnitSelectionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            unitsAttribute: 'units',
            deleteMessage: 'oro.product.productunit.delete.confirmation',
            errorTitle: 'oro.product.productunit.delete.error.title',
            errorMessage: 'oro.product.productunit.delete.error.message',
            addButtonSelector: 'a.add-list-item',
            selectParent: '.oro-multiselect-holder',
            dataContent: '*[data-content]',
            unitSelect: 'select[name$="[unit]"]',
            conversionRateInput: 'input[name$="[conversionRate]"]',
            hiddenUnitClass: 'hidden-unit',
            parentTableSelector: '',
            pricesUnitsSelector: 'select[name^="oro_product[prices]"][name$="[unit]"]',
            precisions: {}
        },

        /**
         * @property {Object}
         */
        listen: {
            'product:primary:precision:change mediator': 'onPrimaryPrecisionChange'
        },

        /**
         * @inheritDoc
         */
        constructor: function ProductUnitSelectionLimitationsComponent() {
            ProductUnitSelectionLimitationsComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('content:changed', _.bind(this.onChange, this))
                .on('content:remove', _.bind(this.askConfirmation, this))
                .on('click', '.removeLineItem', _.bind(this.onRemoveRow, this))
                .on('change', this.options.unitSelect, _.bind(this.onSelectChange, this));

            mediator.on('page:afterChange', this.onChange, this);
        },

        /**
         * Return add button
         *
         * @returns {jQuery.Element}
         */
        getAddButton: function() {
            return this.options._sourceElement.find(this.options.addButtonSelector);
        },

        /**
         * Handle remove row
         *
         * @param {jQuery.Event} e
         */
        onRemoveRow: function(e) {
            e.stopPropagation();
            var option = $(e.target).closest(this.options.selectParent).find(
                this.options.unitSelect + ' option:selected'
            );
            var unitsWithPrices = this.getUnitsWithPrices();
            var val = option.val();
            if (unitsWithPrices[val] !== undefined) {
                this.showError();
            } else {
                $(e.target).closest(this.options.dataContent).trigger('content:remove');
            }
        },

        /**
         * Handle change select
         */
        onChange: function() {
            var selects = this.options._sourceElement.find(this.options.unitSelect);
            var primary = _.first(_.values(this.getPrimaryData()));

            this.toggleTableVisibility();

            _.each(selects.get(), function(select) {
                select = $(select);
                if (primary) {
                    select.find('option[value="' + primary + '"]').remove();
                }

                var option = select.find('option:selected');
                selects.not(select).find('option[value="' + option.val() + '"]').remove();

                if (select.find('option').length <= 1) {
                    this.getAddButton().hide();
                }

                if (option.val() !== select.data('prevValue') && !select.hasClass(this.options.hiddenUnitClass)) {
                    var value = this.options.precisions[option.val()];

                    if (!_.isUndefined(value)) {
                        select.parents(this.options.selectParent).find('input[class="precision"]').val(value);
                    }
                }

                select
                    .data('prevValue', option.val())
                    .data('prevText', option.text());

                this.addData({value: option.val(), text: option.text()});
            }, this);

            _.each(this.getPrimaryData(), function(text, val) {
                this.addConversionRateLabels(val);
            }, this);
        },

        /**
         *  Handle changes in primary precision
         */
        onPrimaryPrecisionChange: function(e) {
            var removed = e.removed;
            var added = e.added;
            var self = this;
            if (!_.isEmpty(removed)) {
                _.each(removed, function(text, val) {
                    self.addOptionToAllSelects(val, text);
                });
            }
            if (!_.isEmpty(added)) {
                _.each(added, function(text, val) {
                    self.addConversionRateLabels(val);
                    self.removeOptionFromAllSelects(val, text);
                });
            }
        },

        /**
         * Handle remove item
         *
         * @param {jQuery.Event} e
         */
        onRemoveItem: function(e) {
            var option = $(e.target).find('select:enabled option:selected');

            if (option) {
                this.removeData({value: option.val(), text: option.text()});
                this.addOptionToAllSelects(option.val(), option.text());
                this.getAddButton().show();
                $(e.target).remove();
            }
            this.toggleTableVisibility();
        },

        /**
         * Handle select change
         *
         * @param {jQuery.Event}  e
         */
        onSelectChange: function(e) {
            var select = $(e.target);
            this.removeData({value: select.data('prevValue'), text: select.data('prevText')});
            this.addOptionToAllSelects(select.data('prevValue'), select.data('prevText'));
            this.onChange();
        },

        /**
         * Add available options to selects
         *
         * @param {String} value
         * @param {String} text
         */
        addOptionToAllSelects: function(value, text) {
            this.options._sourceElement.find(this.options.unitSelect).each(function() {
                var select = $(this);

                if (select.data('prevValue') !== value) {
                    select.append($('<option></option>').val(value).text(text));
                }
            });
        },

        /**
         * Add label to all conversionRates inputs
         *
         * @param {String} value
         */
        addConversionRateLabels: function(value) {
            this.options._sourceElement.find(this.options.conversionRateInput).each(function() {
                var input = $(this);
                var text = __('oro.product.product_unit.' + value + '.label.short_plural');
                input.parent('td').find('span').remove();
                input.parent('td').append($('<span></span>').html('<em>&nbsp;</em>' + text.toLowerCase()).addClass(
                    'conversion-rate-label'
                ));
            });
        },

        /**
         * Remove options from all selects
         *
         * @param {String} value
         * @param {String} text
         */
        removeOptionFromAllSelects: function(value, text) {
            this.options._sourceElement.find(this.options.unitSelect).each(function() {
                var select = $(this);
                var option = select.find('option[value="' + value + '"]');
                if (option.length) {
                    option.remove();
                }
            });
        },

        /**
         * Ask delete confirmation
         *
         * @param {jQuery.Event} e
         */
        askConfirmation: function(e) {
            var confirmModal = new DeleteConfirmation({
                content: __(this.options.deleteMessage)
            });

            confirmModal.on('ok', this.onRemoveItem.bind(this, e));
            confirmModal.open();
        },

        /**
         * Show error
         *
         */
        showError: function() {
            var confirmModal = new DeleteConfirmation({
                title: __(this.options.errorTitle),
                content: __(this.options.errorMessage),
                allowOk: false
            });

            confirmModal.open();
        },

        /**
         * @param {Object} data with structure {value: value, text: text}
         */
        addData: function(data) {
            var storedData = this.getData();
            var primaryData = this.getPrimaryData();
            if (storedData.hasOwnProperty(data.value) || primaryData.hasOwnProperty(data.value)) {
                return;
            }

            storedData[data.value] = data.text;

            this.saveData(storedData);
            this.triggerAddEvent(storedData);
        },

        /**
         * @param {Object} data with structure {value: value, text: text}
         */
        removeData: function(data) {
            var storedData = this.getData();
            delete storedData[data.value];

            this.saveData(storedData);
            this.triggerRemoveEvent(storedData);
        },

        /**
         * Return units from data attribute
         *
         * @returns {jQuery.Element}
         */
        getData: function() {
            return this.options._sourceElement.data(this.options.unitsAttribute) || {};
        },

        getPrimaryData: function() {
            return $(':data(' + this.options.unitsAttribute + ')').data(this.options.unitsAttribute) || {};
        },

        getUnitsWithPrices: function() {
            var selects = $(this.options.pricesUnitsSelector);
            var unitsWithPrice = {};
            _.each(selects, function(select) {
                var selected = $(select).find('option:selected');
                unitsWithPrice[selected.val()] = selected.text();
            });

            return unitsWithPrice;
        },

        /**
         * Save data to data attribute
         *
         * @param {Object} data
         */
        saveData: function(data) {
            this.options._sourceElement.data(this.options.unitsAttribute, data);
        },

        /**
         * Toggle Table visibility
         */
        toggleTableVisibility: function() {
            var selects = this.options._sourceElement.find(this.options.unitSelect);
            var table = this.options._sourceElement.find('table');

            if (selects.length < 1) {
                table.hide();
                if (this.getAddButton().length === 0) {
                    this.options._sourceElement.parents('.control-group:first').hide();
                }
            } else {
                table.show();
            }
        },

        /**
         * Trigger add event
         *
         * @param {Object} data
         */
        triggerAddEvent: function(data) {
            mediator.trigger('product:precision:add', data);
        },

        /**
         * Trigger add event
         *
         * @param {Object} data
         */
        triggerRemoveEvent: function(data) {
            mediator.trigger('product:precision:remove', data);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.options._sourceElement.off();

            ProductUnitSelectionLimitationsComponent.__super__.dispose.call(this);
        }
    });

    return ProductUnitSelectionLimitationsComponent;
});
