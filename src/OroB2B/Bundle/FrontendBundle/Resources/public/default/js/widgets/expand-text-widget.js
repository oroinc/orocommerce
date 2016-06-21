define(['jquery', 'jquery-ui'], function($) {
    'use strict';

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
            if (text.length <= this.options.maxLength) {
                this.$el.text(text);
                return;
            }

            var shortText = text.substr(0, this.options.maxLength) + this.options.clipSymbols;
            var $trigger = this._createNode('span', this.triggerClass)
                .append(this._createNode('i', this.options.iconClass));
            var $shortContent = this._createNode('span', this.contentShoerClass, shortText);
            var $longContent = this._createNode('span', this.contentLongClass, text);

            var $content = this._createNode('div', this.contentClass);
            $content
                .append($trigger)
                .append($shortContent)
                .append($longContent);

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

        _createNode: function(tag, className, content) {
            return $('<' + tag + '/>')
                .addClass(className || '')
                .html(content || '');
        }
    });

    return 'expandLongTexWidget';
});
