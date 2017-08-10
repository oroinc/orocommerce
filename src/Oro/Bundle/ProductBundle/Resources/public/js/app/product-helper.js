define(function(require) {
    'use strict';

    var ProductHelper;
    var localeSettings = require('orolocale/js/locale-settings');
    var _ = require('underscore');
    var tools = require('oroui/js/tools');

    var decimalSeparator = localeSettings.getNumberFormats('decimal').decimal_separator_symbol;
    var namespace = 'product-helper-' + _.uniqueId();

    function onKeypressForbid(event) {
        var keyCode = event.originalEvent.charCode;
        var keyName = event.originalEvent.key || String.fromCharCode(keyCode);
        var specialKeys = [97, 99, 118];
        var precision = event.data.precision;

        if (event.target.value.length && precision > 0 && decimalSeparator === keyName) {
            event.target.value = parseInt(event.target.value).toFixed(precision);

            if (decimalSeparator !== '.') {
                event.target.value = event.target.value.replace('.', decimalSeparator);
            }

            if (!_.isUndefined(event.target.selectionStart)) {
                event.target.selectionEnd = event.target.value.length;
                event.target.selectionStart = event.target.value.length - precision;
            }

            event.stopPropagation();
            event.preventDefault();
            return false;
        }

        if (
            keyCode > 47 && keyCode < 58 ||
            keyName === 'Backspace' ||
            (_.contains(specialKeys, keyCode) && (event.originalEvent.metaKey || event.originalEvent.ctrlKey) )
        ) {
            return true;
        }

        event.stopPropagation();
        event.preventDefault();
        return false;
    }

    function forbidQuantityField(event) {
        var regExpString = '^([0-9]*)';
        var precision = event.data.precision;

        if (precision > 0) {
            regExpString += '(\\' + decimalSeparator + '{1})?([0-9]{1,' + precision + '})?';
        }

        var regExp = new RegExp(regExpString, 'g');

        if (!_.isUndefined(event.target.value)) {

            if (precision === 0) {
                event.target.value = event.target.value.replace(/^0*/g, '');
            }

            event.target.value = event.target.value
                .replace(/(?!\.)[?:\D+]/g, '');

            if (event.target.value[0] === decimalSeparator && precision > 0) {
                event.target.value = '0' + event.target.value;
            }

            var substitution = event.target.value.replace(regExp, '');

            if (!regExp.test(event.target.value) || substitution.length > 0) {
                event.target.value = event.target.value.match(regExp).join('');
                event.preventDefault();
                return false;
            }
        }
    }

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
         * @param element
         * @param precision
         */
        normalizeNumberField: function(element, precision) {
            if (tools.isDesktop()) {
                element.attr('type', 'text');
            }

            if (tools.isMobile()) {
                element.attr('pattern', precision === 0 ? '[0-9]*' : '');
            }

            element
                .off(['input.' + namespace, 'keypress.' + namespace].join(' '))
                .on('input.' + namespace, {precision: precision}, forbidQuantityField)
                .on('keypress.' + namespace, {precision: precision}, onKeypressForbid);
        }
    };

    return ProductHelper;
});
