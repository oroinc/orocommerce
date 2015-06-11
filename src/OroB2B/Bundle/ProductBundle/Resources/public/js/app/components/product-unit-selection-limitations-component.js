/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductUnitSelectionLimitationsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        DeleteConfirmation = require('oroui/js/delete-confirmation'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator');

    ProductUnitSelectionLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            unitsAttribute: 'units',
            deleteMessage: 'orob2b.product.productunit.delete.confirmation',
            addButtonSelector: 'a.add-list-item',
            selectParent: '.oro-multiselect-holder',
            dataContent: '*[data-content]',
            unitSelect: 'select[name$="[unit]"]',
            hiddenUnitClass: 'hidden-unit',
            precisions: {}
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('content:changed', _.bind(this.onChange, this))
                .on('content:remove', _.bind(this.askConfirmation, this))
                .on('click', '.removeRow', _.bind(this.onRemoveRow, this));

            this.options._sourceElement.trigger('content:changed');
        },

        /**
         * Return add button
         *
         * @returns {jQuery.Element}
         */
        getAddButton: function () {
            return this.options._sourceElement.find(this.options.addButtonSelector);
        },

        /**
         * Handle remove row
         *
         * @param {jQuery.Event} e
         */
        onRemoveRow: function (e) {
            e.stopPropagation();

            $(e.target).closest(this.options.dataContent).trigger('content:remove');
        },

        /**
         * Handle change select
         */
        onChange: function () {
            var selects = this.options._sourceElement.find(this.options.unitSelect),
                self = this;

            selects.each(function (index) {
                var select = $(this);

                selects.each(function (_index) {
                    if (index == _index) {
                        return;
                    }

                    var option = $(this).find("option[value='" + select.val() + "']");

                    if (option) {
                        option.remove();
                    }
                });

                if (select.find('option').length <= 1) {
                    self.getAddButton().hide();
                }

                var option = select.find('option:selected');

                if (option.val() != select.data('prevValue') && !select.hasClass(self.options.hiddenUnitClass)) {
                    var value = self.options.precisions[option.val()];

                    if (value != undefined) {
                        select.parents(self.options.selectParent).find('input').val(value);
                    }
                }

                select
                    .data('prevValue', option.val())
                    .data('prevText', option.text())
                    .on('change', _.bind(self.onSelectChange, self));

                self.addData({value: option.val(), text: option.text()});
            });
        },

        /**
         * Handle remove item
         *
         * @param {jQuery.Event} e
         */
        onRemoveItem: function (e) {
            var option = $(e.target).find('select option:selected');

            if (option) {
                this.removeData({value: option.val(), text: option.text()});
                this.addOptionToAllSelects(option.val(), option.text());
                this.getAddButton().show();
                $(e.target).remove();
            }
        },

        /**
         * Handle select change
         *
         * @param {jQuery.Event}  e
         */
        onSelectChange: function (e) {
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
        addOptionToAllSelects: function (value, text) {
            this.options._sourceElement.find(this.options.unitSelect).each(function () {
                var select = $(this);

                if (select.data('prevValue') != value) {
                    select.append($('<option></option>').val(value).text(text));
                }
            });
        },

        /**
         * Ask delete confirmation
         *
         * @param {jQuery.Event} e
         */
        askConfirmation: function (e) {
            if (!this.confirm) {
                this.confirm = new DeleteConfirmation({
                    content: __(this.options.deleteMessage)
                });
            }

            this.confirm
                .off('ok')
                .on('ok', _.bind(function () {
                    this.onRemoveItem(e);
                }, this))
                .open();
        },

        /**
         * @param {Object} data with structure {value: value, text: text}
         */
        addData: function (data) {
            var storedData = this.getData();
            if (storedData.hasOwnProperty(data.value)) {
                return;
            }

            storedData[data.value] = data.text;

            this.saveData(storedData);
            this.triggerAddEvent(storedData);
        },

        /**
         * @param {Object} data with structure {value: value, text: text}
         */
        removeData: function (data) {
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
        getData: function () {
            return this.options._sourceElement.data(this.options.unitsAttribute) || {}
        },

        /**
         * Save data to data attribute
         *
         * @param {Object} data
         */
        saveData: function (data) {
            this.options._sourceElement.data(this.options.unitsAttribute, data);
        },

        /**
         * Trigger add event
         *
         * @param {Object} data
         */
        triggerAddEvent: function (data) {
            mediator.trigger('product:precision:add', data);
        },

        /**
         * Trigger add event
         *
         * @param {Object} data
         */
        triggerRemoveEvent: function (data) {
            mediator.trigger('product:precision:remove', data);
        },

        dispose: function () {
            if (this.disposed) {
                return;
            }
            if (this.confirm) {
                this.confirm
                    .off()
                    .remove();
            }

            this.options._sourceElement.off();

            ProductUnitSelectionLimitationsComponent.__super__.dispose.call(this);
        }
    });

    return ProductUnitSelectionLimitationsComponent;
});
