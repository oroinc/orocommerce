export default {
    /**
     * @export oropayment/js/normalizer/credit-card-number
     */
    normalize: function(number) {
        return number.replace(/[ -]/g, '');
    }
};
