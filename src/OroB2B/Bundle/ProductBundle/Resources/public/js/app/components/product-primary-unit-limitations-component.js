/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var ProductPrimaryUnitLimitationsComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator');

    ProductPrimaryUnitLimitationsComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            unitsAttribute: 'units',
            allUnitsAttribute: 'all_units',
            deleteMessage: 'orob2b.product.productunit.delete.confirmation',
            addButtonSelector: 'a.add-list-item',
            selectParent: '.oro-multiselect-holder',
            dataContent: '*[data-content]',
            unitSelect: 'select[name$="[unit]"]',
            hiddenUnitClass: 'hidden-unit',
            precisions: {}
        },

        /**
         * @property {Object}
         */
        listen: {
            'product:precision:remove mediator': 'onAdditionalPrecisionsChange',
            'product:precision:add mediator': 'onAdditionalPrecisionsChange',
        },

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.options = _.defaults(options || {}, this.options);

            this.options._sourceElement
                .on('change', _.bind(this.onChange, this));
            this.saveInitialOptions();
            this.options._sourceElement.trigger('change');
        },

        /**
         * Handle change select
         */
        onChange: function () {
            var select = this.options._sourceElement.find(this.options.unitSelect);
            var option = select.find('option:selected');
            var changes = {};
            changes['removed'] = this.getData() || {};
            this.saveData({});
            var storedData = this.getData();

            if (option.val() != undefined) {
                storedData[option.val()] = option.text();
            } else {
                storedData = changes['removed'];
            }
            this.saveData(storedData);
            changes['added'] = storedData;
            
            if(changes['added'] != changes['removed']){
                this.triggerChangeEvent(changes);
            }
        },

        /**
         *  Handle changes in additional precisions
         */
        onAdditionalPrecisionsChange: function (e) {
            var additionalPrecisions =e;
            var precisions = this.getInitialOptions();
            _.each(additionalPrecisions, function(val,key){
                delete precisions[key]
            });

            var select = this.options._sourceElement.find(this.options.unitSelect);
            var options = select.find('option');
            var selected = select.find('option:selected');
            delete precisions[selected.val()];

            _.each(options, function(option){
                if (option.value != selected.val()) {
                    option.remove();
                }
            });
            _.each(precisions, function(text,val){
                select.append($('<option></option>').val(val).text(text));
            }); 
            $(select).find(selected.val()).selected(true).trigger('change');
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
         * Return initial full options from data attribute
         *
         * @returns {jQuery.Element}
         */
        getInitialOptions: function () {
            return _.clone(this.options._sourceElement.data(this.options.allUnitsAttribute) || {});
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
         * Save initial full select options to data attribute
         */
        saveInitialOptions: function () {
            var select = this.options._sourceElement.find(this.options.unitSelect);
            var options = select.find('option');
            var allUnits = {};
            _.each(options, function(option){
                allUnits[option.value] = option.text;
            });
            this.options._sourceElement.data(this.options.allUnitsAttribute, allUnits);
        },

        /**
         * Trigger add event
         *
         * @param {Object} data
         */
        triggerChangeEvent: function (data) {
            mediator.trigger('product:primary:precision:change', data);
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

            ProductPrimaryUnitLimitationsComponent.__super__.dispose.call(this);
        }
    });

    return ProductPrimaryUnitLimitationsComponent;
});
