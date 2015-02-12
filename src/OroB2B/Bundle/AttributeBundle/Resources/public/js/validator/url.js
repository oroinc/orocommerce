/*global define*/
define([
    'underscore', 'oroform/js/validator/url'
], function (_, urlConstraint) {
    'use strict';

    var constraint = _.clone(urlConstraint);

    constraint[0] = 'OroB2B\\Bundle\\AttributeBundle\\Validator\\Constraints\\Url';

    return constraint;
});
