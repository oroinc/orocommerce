/*jslint nomen:true*/
/*global define*/
define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var routing = require('routing');
    var messenger =  require('oroui/js/messenger');
    var __ = require('orotranslation/js/translator');

    return BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            unitsAttribute: 'units',
            selectSelector: 'select[name^=\'orob2b_product[product_shipping_options]\'][name$=\'[productUnit]\']',
            routeFreightClassUpdate: 'orob2b_shipping_freight_classes',
            errorMessage: 'Sorry, unexpected error was occurred',
            selectors: {
                itemsContainer: 'table.list-items',
                itemContainer: 'table tr.list-item',
                freightClassSelector: '.freight-class-select',
                freightClassUpdateSelector: '.freight-class-update-trigger',
                addButtonSelector: '.product-shipping-options-collection .add-list-item'
            }
        },

        listen: {
            'product:precision:remove mediator': 'onContentChanged',
            'product:precision:add mediator': 'onContentChanged'
        },

        /**
         * @property {LoadingMaskView}
         */
        loadingMaskView: null,

        /**
         * @property {jQuery}
         */
        $itemsContainer: null,

        /**
         * @property {jQuery}
         */
        $freightClassesSelect: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.$itemsContainer = this.$el.find(this.options.selectors.itemsContainer);

            this.$el
                .on('content:changed', _.bind(this.onContentChanged, this))
                .on('content:remove', _.bind(this.onContentRemoved, this))
            ;
            this.$itemsContainer.on('click', '.removeRow', _.bind(this.onContentChanged, this));

            this.loadingMaskView = new LoadingMaskView({container: this.options.selectors.itemsContainer});

            this.$itemsContainer
                .on(
                    'change',
                    this.options.selectors.freightClassUpdateSelector,
                    _.bind(this.onFreightClassTrigger, this)
                )
                .on('change', this.options.selectSelector, _.bind(this.onProductUnitChanged, this))
            ;

            this.onContentChanged();
        },

        onContentChanged: function() {
            var items = this.$el.find(this.options.selectors.itemContainer);
            var self = this;
            var productUnits = this.getProductUnits();
            var selectedUnits = [];

            _.each(this.getSelects(), function(select) {
                var $select = $(select);
                var clearChangeRequired = self.clearOptions(productUnits, $select);
                var addChangeRequired = self.addOptions(productUnits, $select);
                if (clearChangeRequired || addChangeRequired) {
                    $select.trigger('change');
                }
                if (_.indexOf(selectedUnits, $select.val()) === -1) {
                    selectedUnits.push($select.val());
                }
            });

            if (items.length > 0) {
                this.$itemsContainer.show();
            }
            $(this.options.selectors.addButtonSelector).toggle(
                _.keys(productUnits).length > selectedUnits.length
            );
        },

        onProductUnitChanged: function() {
            var units = this.getProductUnits();
            var selectedUnits = [];
            var selectedUnitsBefore = [];

            _.each(this.getSelects(), function(select) {
                var value = $(select).val();
                if (_.indexOf(selectedUnits, value) === -1) {
                    selectedUnits.push(value);
                }
            });

            _.each(this.getSelects(), function(select) {
                var $select = $(select);
                var currentValue = $select.val();
                $select.find('option').remove();
                if (_.indexOf(selectedUnitsBefore, currentValue) === -1) {
                    selectedUnitsBefore.push(currentValue);
                } else {
                    currentValue = '';
                }
                $.each(units, function(code, label) {
                    if (code === currentValue || _.indexOf(selectedUnits, code) === -1) {
                        $select.append($('<option/>').val(code).text(label));
                    }
                });
                $select.val(currentValue);
            });
        },

        onFreightClassTrigger: function(e) {
            var self = this;
            var $itemContainer = $(e.target).closest(this.options.selectors.itemContainer);
            this.$freightClassesSelect = $itemContainer.find(this.options.selectors.freightClassSelector);
            var $form = $itemContainer.closest('form');
            var formData = $form.find(':input[data-ftid]').serialize();
            formData = formData +
                '&activeUnitCode=' +
                encodeURI($itemContainer.find(this.options.selectSelector).val());
            $.ajax({
                url: routing.generate(this.options.routeFreightClassUpdate),
                type: 'post',
                data: formData,
                beforeSend: $.proxy(this._beforeSend, this),
                success: $.proxy(this._success, this),
                complete: $.proxy(this._complete, this),
                error: $.proxy(function(jqXHR) {
                    this._dropValues(true);
                    messenger.showErrorMessage(__(self.options.errorMessage), jqXHR.responseJSON);
                }, this)
            });
        },

        onContentRemoved: function() {
            var items = this.$el.find(this.options.selectors.itemContainer);

            if (items.length === 0) {
                this.$itemsContainer.hide();
            }
        },

        /**
         * Return units from data attribute of Product
         *
         * @returns {Object}
         */
        getProductUnits: function() {
            return $(':data(' + this.options.unitsAttribute + ')').data(this.options.unitsAttribute) || {};
        },

        /**
         * Return selects to update
         *
         * @returns {jQuery.Element}
         */
        getSelects: function() {
            return this.$itemsContainer.find(this.options.selectSelector);
        },

        /**
         * Clear options from selects
         *
         * @param {Array} units
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        clearOptions: function(units, $select) {
            var updateRequired = false;

            _.each($select.find('option'), function(option) {

                if (!option.value) {
                    return;
                }

                if (!units.hasOwnProperty(option.value)) {
                    option.remove();

                    updateRequired = true;

                    return;
                }
                var $option = $(option);
                if (!$option.is(':selected')) {
                    option.remove();

                    updateRequired = true;
                }
            });

            return updateRequired;
        },

        /**
         * Add options based on units configuration
         *
         * @param {Array} units
         * @param {jQuery.Element} $select
         *
         * @return {Boolean}
         */
        addOptions: function(units, $select) {
            var updateRequired = false,
                emptyOption = $select.find('option[value=""]');

            if (_.isEmpty(units)) {
                emptyOption.show();
            } else {
                emptyOption.hide();
            }

            _.each(units, function(text, value) {
                if (!$select.find("option[value='" + value + "']").length) {
                    $select.append('<option value="' + value + '">' + text + '</option>');
                    updateRequired = true;
                }
            });

            if ($select.val() === '' && !_.isEmpty(units)) {
                var value = _.keys(units)[0];
                $select.val(value);
                $($select.find('option')[0]).attr('selected', 'selected');
                updateRequired = true;
            }

            return updateRequired;
        },

        /**
         * @private
         *
         * @param {Boolean} disabled
         */
        _dropValues: function(disabled) {
            this.$freightClassesSelect
                .prop('disabled', disabled)
                .val(null)
                .find('option')
                .remove();
        },

        /**
         * @private
         */
        _beforeSend: function() {
            if (this.loadingMaskView) {
                this.loadingMaskView.show();
            }
        },

        /**
         * @param {Object} data
         *
         * @private
         */
        _success: function(data) {
            var self = this;
            var units = data.units;
            var disabled = _.isEmpty(units);
            var value = this.$freightClassesSelect.val();
            this._dropValues(disabled);
            if (!_.isEmpty(units)) {
                $.each(units, function(code, label) {
                    if (!self.$freightClassesSelect.find('option[value=' + code + ']').length) {
                        self.$freightClassesSelect.append($('<option/>').val(code).text(label));
                    }
                });
                self.$freightClassesSelect.find('option[value=""]').hide();
                self.$freightClassesSelect.val(value);
                if (self.$freightClassesSelect.val() === null) {
                    self.$freightClassesSelect.val(_.keys(units)[0]);
                }
            } else {
                self.$freightClassesSelect.append($('<option/>').val('').text(''));
                self.$freightClassesSelect.val('');
            }
        },

        /**
         * @private
         */
        _complete: function() {
            this.$freightClassesSelect
                .trigger('value:changed')
                .trigger('change');
            if (this.loadingMaskView) {
                this.loadingMaskView.hide();
            }
        }
    });
});
