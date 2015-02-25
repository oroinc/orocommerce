/*global define*/
define([
    'underscore', 'oroform/js/validator/regex'
], function (_, regexConstraint) {
    'use strict';

    var constraint = _.clone(regexConstraint);

    constraint[0] = 'OroB2B\\Bundle\\ValidationBundle\\Validator\\Constraints\\UrlSafe';

    return constraint;
});
