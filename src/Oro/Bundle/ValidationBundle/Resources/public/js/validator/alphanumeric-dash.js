define([
    'underscore', 'oroform/js/validator/regex'
], function(_, regexConstraint) {
    'use strict';

    const constraint = _.clone(regexConstraint);

    constraint[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\AlphanumericDash';

    return constraint;
});
