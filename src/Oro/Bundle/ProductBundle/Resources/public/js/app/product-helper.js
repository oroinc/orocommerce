define(function(require) {
    'use strict';

    const _ = require('underscore');

    const ProductHelper = {
        trimWhiteSpace: function(val) {
            val = val.replace(/(\n|\r\n|^)\s+/g, '$1')// trim white space in each line start
                .replace(/\s+(\n|\r\n|$)/g, '$1');// trim white space in each line end

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
        }
    };

    return ProductHelper;
});
