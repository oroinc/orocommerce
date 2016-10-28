define(function(require) {
    'use strict';

    var RuleEditorView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var _ = require('underscore');

    RuleEditorView = BaseView.extend({
        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.component = options.component;

            this.$el.on('keyup paste blur', _.debounce(_.bind(this.validate, this), 50));

            this.initAutocomplete();
        },

        validate: function() {
            var isValid = this.component.isValid(this.$el.val());
            this.$el.toggleClass('error', !isValid);
            this.$el.parent().toggleClass('validation-error', !isValid);
        },

        initAutocomplete: function() {
            var _position;
            var component = this.component;
            var $el = this.$el;
            var el = $el[0];

            $el.typeahead({
                minLength: 0,
                items: 10,
                source: function(value) {
                    var sourceData = component.getAutocompleteSource(value || '', el.selectionStart);

                    clickHandler = clickHandler.bind(this);

                    _position = sourceData.position;

                    return sourceData.array;
                },
                matcher: function() {
                    //we already match
                    return true;
                },
                updater: function(item) {
                    //place selected value into right position
                    var update  = component.getUpdateValue(this.query, item, _position);
                    setTimeout(function() {
                        el.selectionStart = update.position;
                        $el.trigger('keyup');
                    }, 10);
                    return update.value;
                },
                focus: function() {
                    this.focused = true;

                    clickHandler.apply(this, arguments);
                },
                highlighter: function(item) {
                    var query = (component.getWordUnderCaret(this.query) || this.query).replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&');

                    return item.replace(new RegExp('(' + query + ')', 'ig'), function($1, match) {
                        return '<strong>' + match + '</strong>'
                    });
                },
                lookup: function() {
                    this.query = $el.val() || '';

                    var items = $.isFunction(this.source) ? this.source(this.query, $.proxy(this.process, this)) : this.source;

                    return items ? this.process(items) : this;
                }
            });

            $el.on('click', function() {
                clickHandler.apply($el, arguments);
            });

            function clickHandler() {
                var _this = this;
                var _arguments = arguments;

                setTimeout(function() {
                    _this.keyup.apply(_this, _arguments);
                }, 10);
            }
        }
    });

    return RuleEditorView;
});
