define(['jquery', 'jquery-ui'], function($) {
    'use strict';

    /**
     * Condition builder widget
     */
    $.widget('oroui.expandLongTexWidget', {

        options: {
            maxLength: 80,
            clipSymbols: ' ...',
            containerClass: 'expand-text',
            iconClass: 'cf-play',
            openClass: 'open'
        },

        _create: function() {
            this._super();
            this.$el = this.element;
            this._prepareClasses();
        },

        _prepareClasses: function() {
            this.triggerClass = this.options.containerClass + '__trigger';
            this.contentClass = this.options.containerClass + '__container';
            this.textClass = this.options.containerClass + '__content';
            this.contentShoerClass = this.textClass + ' ' + this.textClass + '--short';
            this.contentLongClass = this.textClass + ' ' + this.textClass + '--long';
        },

        _init: function() {
            var text = this.$el.text().trim();
            var $content = this._createContainer(null, this.contentClass);

            if (text.length > this.options.maxLength) {
                var shortText = text.substr(0, this.options.maxLength) + this.options.clipSymbols;
                var $trigger = this._createContainer('span', this.triggerClass)
                                    .append(this._createContainer('i', this.options.iconClass));
                var $shortContent = this._createContainer('span', this.contentShoerClass, shortText);
                var $longContent = this._createContainer('span', this.contentLongClass, text);

                $content
                    .append($trigger)
                    .append($shortContent)
                    .append($longContent);
            } else {
                $content.append(text);
            }

            this.$el
                .html($content)
                .addClass('init');

            this._initEvents();
        },

        _initEvents: function() {
            var $trigger = this.$el.find('.' + this.triggerClass);

            this._on($trigger, {
                'click': this._onClick
            });
        },

        _onClick: function(event) {
            event.preventDefault();

            this.$el.toggleClass(this.options.openClass);
        },

        _createContainer: function(nodeName, className, content) {
            var node = nodeName || 'div';
            var $node = $('<' + node + '/>');

            if (className) {
                $node.addClass(className);
            }

            if (content) {
                $node.html(content);
            }
            return $node;
        }
    });
});
