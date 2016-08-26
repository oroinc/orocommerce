/*global define*/
define([
    'underscore', 'oroform/js/validator/email'
], function (_, emailConstraint) {
    'use strict';

    var constraint = _.clone(emailConstraint);

    constraint[0] = 'Oro\\Bundle\\ValidationBundle\\Validator\\Constraints\\Email';

    return constraint;
});
