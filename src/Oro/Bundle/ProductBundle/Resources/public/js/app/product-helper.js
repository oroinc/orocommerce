define(function(require) {
    'use strict';

    var ProductHelper;
    var localeSettings = require('orolocale/js/locale-settings');
    var _ = require('underscore');
    var $ = require('jquery');

    var decimalSeparator = localeSettings.getNumberFormats('decimal').decimal_separator_symbol;
    var namespace = '.product-helper-' + _.uniqueId();

    ProductHelper = {
        trimWhiteSpace: function(val) {
            val = val.replace(/(\n|\r\n|^)\s+/g, '$1')//trim white space in each line start
                .replace(/\s+(\n|\r\n|$)/g, '$1');//trim white space in each line end

            return val;
        },

        trimAllWhiteSpace: function(val, acceptedSeparators) {
            acceptedSeparators = acceptedSeparators || [',', ';'];

            val = this.trimWhiteSpace(val);

            _.each(acceptedSeparators, function(separator) {
                val = val.replace(new RegExp(' *' + separator + ' *', 'g'), separator);
            }, this);

            val = val.replace(/ +/g, ' ');

            return val;
        },

        /**
         * Filter field input, user can type only numbers also filtering takes of precision
         *
         * @param {Object} model
         * @param {jQuery} $el
         * @param {Number} precision
         */
        normalizeNumberField: function(model, $el, precision) {
            model.on('change:product_units', function() {
                this._initNormalizeNumberField(model, $el, precision);
            }, this);
            model.on('change:unit', function() {
                this._initNormalizeNumberField(model, $el, precision);
            }, this);

            this._initNormalizeNumberField(model, $el, precision);
        },

        _initNormalizeNumberField: function(model, $el, precision) {
            if (_.isUndefined(precision)) {
                precision = model.get('product_units')[model.get('unit')] || 0;
            }

            if (_.isDesktop()) {
                $el.attr('type', 'text');
            } else {
                $el.attr('pattern', precision === 0 ? '[0-9]*' : '');
            }

            $el
                .off(namespace)
                .on('input' + namespace, {precision: precision}, _.bind(this._normalizeNumberFieldValue, this))
                .on('change' + namespace, {precision: precision}, _.bind(this._normalizeNumberFieldValue, this))
                .on('keypress' + namespace, {precision: precision}, _.bind(this._addFraction, this))
                .trigger('input');
        },

        _addFraction: function(event) {
            var field = event.target;
            var originalValue = field.value;
            var precision = event.data.precision;

            //set fixed length start
            var keyName = event.key || String.fromCharCode(event.which);
            if (precision > 0 && decimalSeparator === keyName &&
                field.value.length && field.selectionStart === field.value.length) {
                field.value = parseInt(field.value).toFixed(precision);

                if (decimalSeparator !== '.') {
                    field.value = field.value.replace('.', decimalSeparator);
                }

                if (!_.isUndefined(field.selectionStart)) {
                    field.selectionEnd = field.value.length;
                    field.selectionStart = field.value.length - precision;
                }

                this._triggerEventOnValueChange(event, originalValue);

                event.stopPropagation();
                event.preventDefault();
                return false;
            }
            //set fixed length end
        },

        _normalizeNumberFieldValue: function(event) {
            var field = event.target;
            var originalValue = field.value;
            var precision = event.data.precision;
            if (_.isUndefined(field.value)) {
                return;
            }

            //filter value start
            if (precision === 0) {
                field.value = field.value.replace(/^0*/g, '');
            }

            field.value = field.value.replace(/(?!\.)[?:\D+]/g, '');//clear not allowed symbols

            if (field.value[0] === decimalSeparator && precision > 0) {
                field.value = '0' + field.value;
            }
            //filter value end

            //validate value start
            var regExpString = '^([0-9]*)';
            if (precision > 0) {
                regExpString += '(\\' + decimalSeparator + '{1})?([0-9]{1,' + precision + '})?';
            }

            var regExp = new RegExp(regExpString, 'g');
            var substitution = field.value.replace(regExp, '');

            if (!regExp.test(field.value) || substitution.length > 0) {
                field.value = field.value.match(regExp).join('');

                this._triggerEventOnValueChange(event, originalValue);
                event.preventDefault();
                return false;
            } else {
                this._triggerEventOnValueChange(event, originalValue);
            }
            //validate value end
        },

        _triggerEventOnValueChange: function(event, value) {
            var field = event.target;
            if (field.value !== value) {
                $(field).trigger(event);
                return;
            }
        }
    };

    return ProductHelper;
});
