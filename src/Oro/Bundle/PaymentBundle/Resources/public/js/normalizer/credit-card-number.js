define(function() {
    'use strict';

    /**
     * @export oropayment/js/normalizer/credit-card-number
     */
    return {
        normalize: function(number) {
            return number.replace(/[ -]/g, '');
        }
    };
});
