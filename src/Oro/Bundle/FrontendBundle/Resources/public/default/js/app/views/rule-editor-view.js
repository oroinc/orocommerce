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
            var clickHandler = function() {
                var _arguments = arguments;

                setTimeout(_.bind(function() {
                    this.keyup.apply(this, _arguments);
                }, this), 10);
            };

            $el.typeahead({
                minLength: 0,
                items: 10,
                source: function(value) {
                    var sourceData = component.getSuggestData(value || '', el.selectionStart);

                    clickHandler = clickHandler.bind(this);

                    _position = sourceData.position;

                    return sourceData.list;
                },
                matcher: function() {
                    // we already match
                    return true;
                },
                updater: function(item) {
                    var valueHolder = '[]',
                        hasEllipsis = item.indexOf('&hellip;') !== -1,
                        clearItem = item.replace('&hellip;', ''),
                        hasDataSource = component.getDataSource(clearItem),
                        newItem = (hasDataSource ? clearItem + valueHolder : clearItem) + (hasEllipsis ? '.' : ' '),
                        newPos = hasDataSource ? _position.start + newItem.length - 1 - valueHolder.length / 2 : null;

                    return component.setUpdatedValue(this.query, newItem, _position, newPos);
                },
                focus: function() {
                    this.focused = true;

                    clickHandler.apply(this, arguments);
                },
                highlighter: function(item) {
                    return item;
                },
                lookup: function() {
                    this.query = $el.val() || '';

                    var items = $.isFunction(this.source) ? this.source(this.query, $.proxy(this.process, this)) : this.source;

                    return items ? this.process(items) : this;
                }
            });

            // adds handling of 'click' inside focused input
            $el.on('click', function() {
                clickHandler.apply($el, arguments);
            });
        }
    });

    return RuleEditorView;
});
