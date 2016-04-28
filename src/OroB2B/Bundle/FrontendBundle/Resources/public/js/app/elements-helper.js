define(function(require) {
    'use strict';

    /**
     * This helper use in the context of component View
     */
    var $ = require('jquery');
    var _ = require('underscore');

    return {
        $elements: null,

        elements: {},

        elementsEvents: null,

        modelElements: [],

        elementEventNamespace: '.elementEvent',

        initializeElements: function(options) {
            _.extend(this, _.pick(options, ['elements', 'modelElements']));
            this.$elements = this.$elements || {};
            this.elementsEvents = this.elementsEvents || {};

            this.initializeModelElements();
            this.delegateElementsEvents();
        },

        disposeElements: function() {
            this.undelegateElementsEvents();

            var props = ['$elements', 'elements', 'elementsEvents', 'modelElements'];
            for (var i = 0, length = props.length; i < length; i++) {
                delete this[props[i]];
            }
        },

        initializeModelElements: function() {
            if (!this.model) {
                return;
            }
            _.each(this.modelElements, function(key) {
                if (this.elementsEvents[key + ' setModelValue'] === undefined) {
                    this.elementsEvents[key + ' setModelValue'] = ['change', '_setModelValueFromElementOnChange'];
                }
                this.setModelValueFromElement(key);
            }, this);
        },

        delegateElementsEvents: function() {
            _.each(this.elementsEvents, function(eventCallback, eventKey) {
                if (!eventCallback) {
                    return;
                }
                var key = eventKey.split(' ')[0];
                var event = eventCallback[0];
                var callback = eventCallback[1];
                this.delegateElementEvent(key, event, callback);
            }, this);
        },

        delegateElementEvent: function(key, event, callback) {
            if (!_.isFunction(callback)) {
                callback = _.bind(this[callback], this);
            }
            this.getElement(key).on(event + this.elementEventNamespace + this.cid, function(e) {
                callback(e, key);
            });
        },

        undelegateElementsEvents: function() {
            var elementEventNamespace = this.elementEventNamespace + this.cid;
            _.each(this.$elements, function($element) {
                $element.off(elementEventNamespace);
            });
        },

        getElement: function(key) {
            if (this.$elements[key] === undefined) {
                this.$elements[key] = this._findElement(key) || $([]);
            }
            return this.$elements[key];
        },

        _findElement: function(key) {
            if (this.elements[key] === undefined && this[key] !== undefined) {
                return this[key];
            }

            var selector = this.elements[key] || null;
            if (!selector) {
                return null;
            }

            var $context;
            if (!_.isArray(selector)) {
                //selector = '[data-name="element"]'
                $context = this.getElement('$el');
            } else {
                //selector = ['$el', '[data-name="element"]']
                $context = this.getElement(selector[0]);
                selector = selector[1] || null;
            }

            if (!$context || !selector) {
                return null;
            }

            return $context.find(selector);
        },

        setModelValueFromElement: function(key) {
            var $element = this.getElement(key);
            var element = $element.get(0);
            var validator = $element.closest('form').validate();
            if (!$element.length) {
                return false;
            }
            if (!validator || validator.element(element)) {
                this.model.set(key, element.value);
            }
        },

        _setModelValueFromElementOnChange: function(e, key) {
            this.setModelValueFromElement(key);
        }
    };
});
