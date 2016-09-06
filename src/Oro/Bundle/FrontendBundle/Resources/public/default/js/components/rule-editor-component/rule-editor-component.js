define(function(require) {
    'use strict';

    var RuleEditorComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var $ = require('jquery');

    RuleEditorComponent = BaseComponent.extend({
        /**
         *
         * @param options
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            console.log(this.options);
        },

        validate: function() {},

        autocomplete: function() {},
    });

    return RuleEditorComponent;
});
