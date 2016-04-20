/*global define*/
define(['underscore', 'orotranslation/js/translator', 'jquery'
], function (_, __, $) {
    'use strict';

    var defaultParam = {
        message: 'Not valid dates sequence.'
    };

    /**
     * @export orob2bpricing/js/validator/dates-chain
     */
    return [
        'OroB2B\\Bundle\\PricingBundle\\Validator\\Constraints\\DatesChain',
        function (value, element) {
            var noDuplicationFound = true,
                processedPrices = [];

            return false;
        },
        function (param) {
            param = _.extend({}, defaultParam, param);

            return __(param.message, {});
        }
    ];
});
