define(function(require) {
    'use strict';

    /**
     * This helper use in the context of component View
     */
    var $ = require('jquery');
    var _ = require('underscore');
    require('jquery.validate');

    return {
        elementsInitialized: false,

        $elements: null,

        elements: {},

        elementsEvents: null,

        modelElements: {},

        modelEvents: null,

        elementEventNamespace: '.elementEvent',

        deferredInitializeCheck: function(options, checkOptions) {
            var $deferredInitialize = this.$el.parent().closest('[data-layout="deferred-initialize"]');
            if (checkOptions === undefined || !$deferredInitialize.length) {
                return this.deferredInitialize(options);
            }

            var wait = false;
            _.each(checkOptions, function(option) {
                if (options[option] === undefined) {
                    wait = true;
                }
            });

            if (!wait) {
                return this.deferredInitialize(options);
            }

            $deferredInitialize.one('deferredInitialize', _.bind(function(e, deferredOptions) {
                e.preventDefault();
                e.stopPropagation();

                this.deferredInitialize(_.extend({}, options, deferredOptions));
                if (deferredOptions.callback) {
                    deferredOptions.callback(this);
                }
            }, this));
        },

        deferredInitialize: function(options) {
        },

        initializeSubviews: function(options) {
            this._deferredRender();
            var layout = this.$el.data('layout');
            if (layout === 'deferred-initialize') {
                this.$el.trigger('deferredInitialize', options);
                this.handleLayoutInit();
            } else if (layout === 'separate') {
                this.initLayout(options)
                    .done(_.bind(this.handleLayoutInit, this));
            } else {
                this.handleLayoutInit();
            }
        },

        handleLayoutInit: function() {
            this._resolveDeferredRender();
        },

        initializeElements: function(options) {
            this.$html = $('html');
            this.elementsInitialized = true;
            $.extend(true, this, _.pick(options, ['elements', 'modelElements']));
            this.$elements = this.$elements || {};
            this.elementsEvents = $.extend({}, this.elementsEvents || {});
            this.modelEvents = $.extend({}, this.modelEvents || {});

            this.initializeModelElements();
            this.delegateElementsEvents();
        },

        disposeElements: function() {
            if (!this.elementsInitialized) {
                return;
            }
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
                        return this.setModelValueFromElement(e, modelKey, elementKey);
                    }, this)];
                }

                if (this.modelEvents[modelKey + ' setElementValue'] === undefined) {
                    this.modelEvents[modelKey + ' setElementValue'] = ['change', _.bind(function(e) {
                        return this.setElementValueFromModel(e, modelKey, elementKey);
                    }, this)];
                }

                if (this.modelEvents[modelKey + ' focus'] === undefined) {
                    this.modelEvents[modelKey + ' focus'] = ['focus', _.bind(function() {
                        this.getElement(elementKey).focus();
                    }, this)];
                }
            }, this);

            this.setModelValueFromElements();
        },

        setModelValueFromElements: function() {
            _.each(this.modelElements, function(elementKey, modelKey) {
                this.setModelValueFromElement(null, modelKey, elementKey);
            }, this);
        },

        delegateElementsEvents: function() {
            if (!this.elementsInitialized) {
                return;
            }
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
            var self = this;
            if (!_.isFunction(callback)) {
                callback = _.bind(this[callback], this);
            }
            this.getElement(key).on(event + this.elementEventNamespace + this.cid, function(e, options) {
                options = options || {};
                options.manually = self.isChangedManually(this, e);
                callback(e, options);
            });
        },

        delegateModelEvent: function(key, event, callback) {
            if (!_.isFunction(callback)) {
                callback = _.bind(this[callback], this);
            }
            this.model.on(event + ':' + key, function(model, attribute, options) {
                callback(options || {});
            }, this);
        },

        undelegateElementsEvents: function() {
            if (!this.elementsInitialized) {
                return;
            }
            if (this.$elements) {
                var elementEventNamespace = this.elementEventNamespace + this.cid;
                _.each(this.$elements, function ($element) {
                    $element.off(elementEventNamespace);
                });
            }

            if (this.model) {
                this.model.off(null, null, this);//off all events with this context.
            }
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

            if (selector instanceof $) {
                return selector;
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

        setModelValueFromElement: function(e, modelKey, elementKey) {
            var $element = this.getElement(elementKey);
            var element = $element.get(0);
            if (!$element.length) {
                return false;
            }
            var value = $element.val();
            if (value === this.model.get(modelKey)) {
                return;
            }

            var validator = $element.closest('form').validate();
            if (!validator || validator.element(element)) {
                var options = {
                    event: e,
                    manually: this.isChangedManually(element, e)
                };

                this.model.set(modelKey, value, options);
            }
        },

        setElementValueFromModel: function(e, modelKey, elementKey) {
            var $element = this.getElement(elementKey);
            if (!$element.length) {
                return false;
            }
            var value = this.model.get(modelKey);
            if (value === $element.val()) {
                return;
            }

            $element.val(value).change();
        },

        isChangedManually: function(element, e) {
            var manually = false;
            if (e) {
                if (e.manually !== undefined) {
                    manually = e.manually;
                } else {
                    manually = Boolean(e.originalEvent && e.currentTarget === element);
                }
                e.manually = manually;
            }
            return manually;
        }
    };
});
