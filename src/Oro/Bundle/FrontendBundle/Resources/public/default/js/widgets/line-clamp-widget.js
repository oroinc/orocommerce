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
            unSupportedClass: 'line-clamp-polyfill',
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
        },

        _destroy: function() {
            if (this.nativeSupport) {
                this.$el.removeClass(this.options.supportedClass);
            } else {
                this.$el.removeClass(this.options.unSupportedClass);
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
                this.$el.addClass(this.options.unSupportedClass);
            }
        },
    });

    return 'lineClampWidget';
});
