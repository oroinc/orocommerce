define(function(require) {
    'use strict';

    var ProductHelper;
    var localeSettings = require('orolocale/js/locale-settings');
    var _ = require('underscore');

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
         * @param {jQuery} element
         * @param {number} precision
         */
        normalizeNumberField: function(element, precision) {
            if (_.isDesktop()) {
                element.attr('type', 'text');
            } else {
                element.attr('pattern', precision === 0 ? '[0-9]*' : '');
            }

            element
                .off(namespace)
                .on('input' + namespace, {precision: precision}, _.bind(this._normalizeNumberFieldValue, this))
                .on('keypress' + namespace, {precision: precision}, _.bind(this._addFraction, this));
        },

        _addFraction: function(event) {
            var field = event.target;
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

                event.stopPropagation();
                event.preventDefault();
                return false;
            }
            //set fixed length end
        },

        _normalizeNumberFieldValue: function(event) {
            var field = event.target;
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
                event.preventDefault();
                return false;
            }
            //validate value end
        }
    };

    return ProductHelper;
});
