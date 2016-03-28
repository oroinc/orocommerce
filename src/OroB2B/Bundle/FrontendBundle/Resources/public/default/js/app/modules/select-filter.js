define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var SelectFilter = require('oro/filter/select-filter');
    //these changes are needed only for Boolean select filter type for showing label 'All' in filter popup,
    //but do not show it in 'filter-items-hint'
    _.extend(SelectFilter.prototype, {
        placeholder: __('All'),

        /**
         * Get criteria hint value
         *
         * @return {String}
         */
        _getCriteriaHint: function() {
            var value = (arguments.length > 0) ? this._getDisplayValue(arguments[0]) : this._getDisplayValue();
            var choice = _.find(this.choices, function(c) {
                return (c.value === value.value);
            });
            return !_.isUndefined(choice) ? choice.label : null;
        }
    });
});
