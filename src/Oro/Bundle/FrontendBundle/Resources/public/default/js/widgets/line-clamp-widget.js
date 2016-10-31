// Truncating Multiple Line Text
define(function(require) {
    'use strict';

    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    require('jquery-ui');

    $.widget('oroui.lineClampWidget', {
        options: {
            lineClamp: 2,
            supportedClass: 'line-clamp',
            noSupportClass: 'line-clamp-polyfill'
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
            mediator.on('layout:reposition', this._afterRender, this);
        },

        _destroy: function() {
            this.$el.removeClass(this.nativeSupport ? this.options.supportedClass : this.options.noSupportClass);
            delete this.nativeSupport;
        },

        _checkNativeSupportLineClamp: function() {
            // Now native support only webkit browsers
            this.nativeSupport =  '-webkit-line-clamp' in document.body.style ? true : false;
        },

        _getCountLines: function () {
            var lineHeight = parseInt(this.$el.css('line-height'), 10);
            var height = Math.max(this.$el.height(), this.$el.get(0).scrollHeight);

            return Math.round(height / lineHeight);
        },

        _afterRender: function() {
            this._applyLineClamp();
        },

        _applyLineClamp: function() {
            if (this.nativeSupport) {
                this.$el.addClass(this.options.supportedClass);
            } else {
                if (this.options.lineClamp < this._getCountLines()) {
                    this.$el.addClass(this.options.noSupportClass);
                }
            }
        }
    });

    return 'lineClampWidget';
});
