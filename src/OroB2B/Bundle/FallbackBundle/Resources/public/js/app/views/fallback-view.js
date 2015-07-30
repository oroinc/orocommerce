define(function(require) {
    'use strict';

    var FallbackView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orob2bfallback/js/app/views/fallback-view
     * @extends oroui.app.views.base.View
     * @class orob2bfallback.app.views.FallbackView
     */
    FallbackView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                item: '.fallback-item',
                itemValue: '.fallback-item-value',
                itemUseFallback: '.fallback-item-use-fallback',
                itemFallback: '.fallback-item-fallback'
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            var self = this;
            this.initLayout().done(function() {
                self.handleLayoutInit();
            });
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            this.$el.find(this.options.selectors.itemUseFallback).find('input')
                .change(_.bind(this._switchValueType, this))
                .change();
        },

        /**
         * Switch value type
         *
         * @param {Event} e
         * @private
         */
        _switchValueType: function(e) {
            var useFallback = e.currentTarget;
            var isCustom = !useFallback.checked;

            this._enableDisableValue(this.getValueRelatedTo(useFallback), isCustom);
            this._enableDisableFallback(this.getFallbackRelatedTo(useFallback), !isCustom);
        },

        /**
         * Enable/disable value
         *
         * @param {jQuery} value
         * @param {Boolean} enable
         * @private
         */
        _enableDisableValue: function(value, enable) {

            var editor;
            if (value.find('.mce-tinymce').length > 0) {
                editor = value.find('textarea').tinymce();
            }

            if (enable) {
                value.find(':input').removeAttr('disabled');

                if (editor) {
                    editor.getBody().setAttribute('contenteditable', true);
                    $(editor.editorContainer).removeClass('disabled');
                }
            } else {
                value.find(':input').attr('disabled', 'disabled');

                if (editor) {
                    editor.getBody().setAttribute('contenteditable', false);
                    $(editor.editorContainer).addClass('disabled');
                }
            }
        },

        /**
         * Enable/disable fallback
         *
         * @param {jQuery} fallback
         * @param {Boolean} enable
         * @private
         */
        _enableDisableFallback: function(fallback, enable) {
            if (!enable) {
                fallback.find('select').attr('disabled', 'disabled');

                fallback.find('div.selector').addClass('disabled');
            } else {
                fallback.find('select').removeAttr('disabled');

                fallback.find('div.selector').removeClass('disabled');
            }
        },

        /**
         * Find item value element, related to item child
         *
         * @param child
         * @returns {jQuery}
         */
        getValueRelatedTo: function(child) {
            return $(child).closest(this.options.selectors.item)
                .find(this.options.selectors.itemValue);
        },

        /**
         * Find item fallback element, related to item child
         *
         * @param child
         * @returns {jQuery}
         */
        getFallbackRelatedTo: function(child) {
            return $(child).closest(this.options.selectors.item)
                .find(this.options.selectors.itemFallback);
        }
    });

    return FallbackView;
});
