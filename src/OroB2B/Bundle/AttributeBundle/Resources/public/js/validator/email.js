/*global define*/
define([
    'underscore', 'oroform/js/validator/email'
], function (_, emailConstraint) {
    'use strict';

    var constraint = _.clone(emailConstraint);

    constraint[0] = 'OroB2B\\Bundle\\AttributeBundle\\Validator\\Constraints\\Email';

    return constraint;
});
