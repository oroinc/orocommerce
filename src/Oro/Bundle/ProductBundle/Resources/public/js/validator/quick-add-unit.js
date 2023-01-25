import $ from 'jquery';
// @deprecated
export default [
    'QuickAddUnit',
    (value, element) => {
        const validator = $(element).data('unitValidator');
        return !validator || validator.isValid();
    },
    (param, element) => {
        const validator = $(element).data('unitValidator');
        return validator ? validator.getMessage() : '';
    }
];
