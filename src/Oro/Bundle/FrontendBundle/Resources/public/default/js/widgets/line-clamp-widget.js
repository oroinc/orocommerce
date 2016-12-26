// Truncating Multiple Line Text
define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    require('jquery-ui');

    $.widget('oroui.lineClampWidget', {
        options: {
            lineClamp: 2,
            supportedClass: 'line-clamp',
            rendered: false
        },

        _create: function() {
            this.$el = this.element;

            this._super();
            this._checkNativeSupportLineClamp();
        },

        _init: function() {
            this._applyLineClamp();
            this._initEvents();
        },

        _initEvents: function() {
            mediator.once('layout:reposition', this._applyLineClamp, this);

            if (!this.nativeSupport) {
                $(window).on('resize', _.debounce(_.bind(this._onResize, this), 100));
            }
        },

        _destroy: function() {
            if (this.nativeSupport) {
                this.$el.removeClass(this.options.supportedClass);
            } else {
                $(window).off('resize', this._onResize);
                this.$el.text(this.text);
                delete this.text;
            }

            delete this.nativeSupport;
        },

        _checkNativeSupportLineClamp: function() {
            this.nativeSupport =  '-webkit-line-clamp' in document.body.style;
        },

        _applyLineClamp: function() {
            if (this.nativeSupport) {
                this.$el.addClass(this.options.supportedClass);
            } else {
                this.text = this.$el.text().trim();

                var isUpperCased = this.$el.css('text-transform') === 'uppercase';
                this.text = isUpperCased ? this.text.toUpperCase() : this.text;

                var font = this.$el.css('font-size')  + ' ' + this.$el.css('font-family');
                var fullTextWidth = this._getTextWidth(this.text, font);

                if (fullTextWidth <= Math.ceil(this.$el.width())) {
                    return;
                }

                var charsArr = this.text.split('');
                var oneCharWidth = fullTextWidth / charsArr.length;
                var charsInElWidth = Math.floor(this.$el.width() / oneCharWidth);
                var resultText = charsArr.slice(0, charsInElWidth * this.options.lineClamp).join('');

                if (!this.options.rendered) {
                    this.options.rendered = true;
                    this.$el.text(resultText + '...');
                }

                return resultText;
            }
        },

        _onResize: function() {
            var resultText = this._applyLineClamp();
            resultText = resultText.slice(0, resultText.indexOf('...'));
            this.$el.text(resultText + '...');
        },

        _getTextWidth: function(text, font) {
            var canvas = this._getTextWidth.canvas ||
                (this._getTextWidth.canvas = document.createElement('canvas'));
            var context = canvas.getContext('2d');
            context.font = font;

            return context.measureText(text).width;
        }
    });

    return 'lineClampWidget';
});
