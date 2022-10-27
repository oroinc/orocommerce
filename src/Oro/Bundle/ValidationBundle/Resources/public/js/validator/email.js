define([
    'underscore', 'oroform/js/validator/email'
], function(_, emailConstraint) {
    'use strict';

    const constraint = _.clone(emailConstraint);

    constraint[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Email';

    return constraint;
});
