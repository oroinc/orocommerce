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

        modelElements: {},

        modelEvents: null,

        elementEventNamespace: '.elementEvent',

        initializeElements: function(options) {
            $.extend(true, this, _.pick(options, ['elements', 'modelElements']));
            this.$elements = this.$elements || {};
            this.elementsEvents = this.elementsEvents || {};
            this.modelEvents = this.modelEvents || {};

            this.initializeModelElements();
            this.delegateElementsEvents();
        },

        disposeElements: function() {
            this.undelegateElementsEvents();

            var props = ['$elements', 'elements', 'elementsEvents', 'modelElements', 'modelEvents'];
            for (var i = 0, length = props.length; i < length; i++) {
                delete this[props[i]];
            }
        },

        initializeModelElements: function() {
            if (!this.model) {
                return;
            }
            _.each(this.modelElements, function(elementKey, modelKey) {
                if (this.elementsEvents[elementKey + ' setModelValue'] === undefined) {
                    this.elementsEvents[elementKey + ' setModelValue'] = ['change', _.bind(function(e) {
                        return this.setModelValueFromElement(modelKey, elementKey);
                    }, this)];
                }

                if (this.modelEvents[modelKey + ' setElementValue'] === undefined) {
                    this.modelEvents[modelKey + ' setElementValue'] = ['change', _.bind(function(e) {
                        return this.setElementValueFromModel(modelKey, elementKey);
                    }, this)];
                }

                this.setModelValueFromElement(modelKey, elementKey);
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

            _.each(this.modelEvents, function(eventCallback, eventKey) {
                if (!eventCallback) {
                    return;
                }
                var key = eventKey.split(' ')[0];
                var event = eventCallback[0];
                var callback = eventCallback[1];
                this.delegateModelEvent(key, event, callback);
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

        delegateModelEvent: function(key, event, callback) {
            if (!_.isFunction(callback)) {
                callback = _.bind(this[callback], this);
            }
            this.model.on(event + ':' + key, function(e) {
                callback(e, key);
            }, this);
        },

        undelegateElementsEvents: function() {
            var elementEventNamespace = this.elementEventNamespace + this.cid;
            _.each(this.$elements, function($element) {
                $element.off(elementEventNamespace);
            });

            this.model.off(null, null, this);//off all events with this context.
        },

        getElement: function(key, $default) {
            if (this.$elements[key] === undefined) {
                this.$elements[key] = this._findElement(key) || $default || $([]);
            }
            return this.$elements[key];
        },

        clearElementsCache: function() {
            this.$elements = {};
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

        setModelValueFromElement: function(modelKey, elementKey) {
            var $element = this.getElement(elementKey);
            if (!$element.length) {
                return false;
            }
            var value = $element.val();
            if (value === this.model.get(modelKey)) {
                return;
            }

            var validator = $element.closest('form').validate();
            if (!validator || validator.element($element.get(0))) {
                this.model.set(modelKey, value);
            }
        },

        setElementValueFromModel: function(modelKey, elementKey) {
            var $element = this.getElement(elementKey);
            if (!$element.length) {
                return false;
            }
            var value = this.model.get(modelKey);
            if (value === $element.val()) {
                return;
            }

            $element.val(value).change();
        }
    };
});
