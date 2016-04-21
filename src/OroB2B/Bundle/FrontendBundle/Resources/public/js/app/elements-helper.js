define(function(require) {
    'use strict';

    /**
     * This helper use in the context of component View
     */
    var $ = require('jquery');
    var _ = require('underscore');

    return {
        options: {
            elements: {}
        },

        $elements: null,

        initializeElements: function(options) {
            this.options.elements = _.extend({}, this.options.elements || {}, options.elements || {});
            this.$elements = {};
        },

        getElement: function(key) {
            if (this.$elements[key] === undefined) {
                this.$elements[key] = this.findElement(key) || $([]);
            }
            return this.$elements[key];
        },

        findElement: function(key) {
            if (this.options.elements[key] === undefined && this[key] !== undefined) {
                return this[key];
            }

            var selector = this.options.elements[key] || null;
            if (!selector) {
                return null;
            }

            var $context;
            if (!_.isArray(selector)) {
                //selector = '[data-role="element"]'
                $context = this.getElement('$el');
            } else {
                //selector = ['$el', '[data-role="element"]']
                $context = this.getElement(selector[0]);
                selector = selector[1] || null;
            }

            if (!$context || !selector) {
                return null;
            }

            return $context.find(selector);
        }
    };
});
