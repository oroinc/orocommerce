define(function(require) {
    'use strict';

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
            if (this.options.elements.el === undefined) {
                this.options.elements.el = ['$el'];
            }
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

            var $element = null;

            var selector = this.options.elements[key] || null;
            if (!selector) {
                return $element;
            }
            if (!_.isArray(selector)) {
                selector = ['el', selector];
            }

            var $context = this.getElement(selector.shift());
            if (!$context) {
                return $element;
            }

            if (selector.length === 0) {
                return $context;
            }

            var method = selector.length > 1 ? selector.shift() : 'find';
            return $context[method](selector.shift());
        }
    };
});
