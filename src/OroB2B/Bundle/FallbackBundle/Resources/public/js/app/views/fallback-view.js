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
        itemsByCode: {},

        /**
         * @property {Object}
         */
        itemToChilds: {},

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
            var self = this;

            this.mapItemsByCode();

            this.getUseFallbackEl(this.$el).each(function() {
                self.switchUseFallback(self.getItemEl(this));
            });

            this.mapItemToChilds();

            this.getValueEl(this.$el).each(function() {
                self.cloneValueToChilds(self.getItemEl(this));
            });

            this.bindEvents();

            this.fixFallbackWidth();
        },

        /**
         * Bind events to controls
         */
        bindEvents: function() {
            var self = this;

            this.getValueEl(this.$el)
                .change(_.bind(this.cloneValueToChildsEvent, this))
                .keyup(_.bind(this.cloneValueToChildsEvent, this));

            this.$el.find(this.options.selectors.itemValue).find('.mce-tinymce').each(function() {
                self.getValueEl(self.getItemEl(this)).tinymce()
                    .on('change', function(e) {
                        $(this.targetElm).change();
                    })
                    .on('keyup', function(e) {
                        $(this.targetElm).change();
                    });
            });

            this.getUseFallbackEl(this.$el)
                .change(_.bind(this.switchUseFallbackEvent, this));

            this.getFallbackEl(this.$el)
                .change(_.bind(this.switchFallbackTypeEvent, this));

        },

        /**
         * Create item code to element mapping
         */
        mapItemsByCode: function() {
            var self = this;

            this.itemsByCode = {};

            this.$el.find(this.options.selectors.item).each(function() {
                var $item = $(this);
                self.itemsByCode[self.getItemCode($item)] = $item;
            });
        },

        /**
         * Create item to childs mapping
         */
        mapItemToChilds: function() {
            var self = this;

            this.itemToChilds = {};

            this.$el.find(this.options.selectors.item).each(function() {
                var $item = $(this);
                var parentItemCode = self.getParentItemCode($item);

                if (!parentItemCode) {
                    return;
                }

                if (self.itemToChilds[parentItemCode] === undefined) {
                    self.itemToChilds[parentItemCode] = [];
                }
                self.itemToChilds[parentItemCode].push($item);
            });
        },

        /**
         * Trigger on value change
         *
         * @param {Event} e
         */
        cloneValueToChildsEvent: function(e) {
            this.cloneValueToChilds(this.getItemEl(e.currentTarget));
        },

        /**
         * Trigger on "use fallback" change
         *
         * @param {Event} e
         */
        switchUseFallbackEvent: function(e) {
            this.switchUseFallback(this.getItemEl(e.currentTarget));
        },

        /**
         * Trigger on fallback change
         *
         * @param {Event} e
         */
        switchFallbackTypeEvent: function(e) {

            var $item = this.getItemEl(e.currentTarget);

            this.mapItemToChilds();

            var parentItemCode = this.getParentItemCode($item);
            if (parentItemCode) {
                var fromValue = this.getValueEl(this.itemsByCode[parentItemCode]);
                var toValue = this.getValueEl($item);
                this.cloneValue(fromValue, toValue);
            } else {
                this.cloneValueToChildsEvent(e);
            }
        },

        /**
         * Clone item value to childs
         *
         * @param {jQuery} $item
         */
        cloneValueToChilds: function($item) {
            var $fromValue = this.getValueEl($item);
            var itemCode = this.getItemCode($item);

            var self = this;
            $.each(this.itemToChilds[itemCode] || [], function() {
                var $toValue = self.getValueEl(this);
                self.cloneValue($fromValue, $toValue);
            });
        },

        /**
         * Enable/disable controls depending on the "use fallback"
         *
         * @param {jQuery} $item
         */
        switchUseFallback: function($item) {
            var $useFallback = this.getUseFallbackEl($item);
            if ($useFallback.length === 0) {
                return ;
            }

            var checked = $useFallback.get(0).checked;

            this.enableDisableValue(this.getValueEl($item), !checked);
            this.enableDisableFallback(this.getFallbackEl($item), checked);
        },

        /**
         * Enable/disable value
         *
         * @param {jQuery} $value
         * @param {Boolean} enable
         */
        enableDisableValue: function($value, enable) {
            var $valueContainer = $value.closest(this.options.selectors.itemValue);

            var editor;
            if ($valueContainer.find('.mce-tinymce').length > 0) {
                editor = $valueContainer.find('textarea').tinymce();
            }

            if (enable) {
                $value.removeAttr('disabled');

                if (editor) {
                    editor.getBody().setAttribute('contenteditable', true);
                    $(editor.editorContainer).removeClass('disabled');
                    $(editor.editorContainer).children('.disabled-overlay').remove();
                }
            } else {
                $value.attr('disabled', 'disabled');

                if (editor) {
                    editor.getBody().setAttribute('contenteditable', false);
                    $(editor.editorContainer).addClass('disabled');
                    $(editor.editorContainer).append('<div class="disabled-overlay"></div>');
                }
            }
        },

        /**
         * Enable/disable fallback
         *
         * @param {jQuery} $fallback
         * @param {Boolean} enable
         */
        enableDisableFallback: function($fallback, enable) {
            var $fallbackContainer = $fallback.closest(this.options.selectors.itemFallback);

            if (enable) {
                $fallback.removeAttr('disabled');

                $fallbackContainer.find('div.selector').removeClass('disabled');
            } else {
                $fallback.attr('disabled', 'disabled');

                $fallbackContainer.find('div.selector').addClass('disabled');
            }

            $fallback.change();
        },

        /**
         * Clone value to another value
         *
         * @param {jQuery} $fromValue
         * @param {jQuery} $toValue
         */
        cloneValue: function($fromValue, $toValue) {
            $fromValue.each(function(i) {
                var toValue = $toValue.get(i);

                if ($(this).is(':checkbox')) {
                    toValue.checked = this.checked;
                } else {
                    $(toValue).val($(this).val());
                }
            });

            $toValue.filter(':first').change();
        },

        /**
         * Get item element by children
         *
         * @param {*|jQuery|HTMLElement} el
         *
         * @returns {jQuery}
         */
        getItemEl: function(el) {
            var $item = $(el);
            if (!$item.is(this.options.selectors.item)) {
                $item = $item.closest(this.options.selectors.item);
            }
            return $item;
        },

        /**
         * Get value element
         *
         * @param {jQuery} $el
         *
         * @returns {jQuery}
         */
        getValueEl: function($el) {
            return $el.find(this.options.selectors.itemValue).find('input, textarea, select');
        },

        /**
         * Get "use fallback" element
         *
         * @param {jQuery} $el
         *
         * @returns {jQuery}
         */
        getUseFallbackEl: function($el) {
            return $el.find(this.options.selectors.itemUseFallback).find('input');
        },

        /**
         * Get fallback element
         *
         * @param {jQuery} $el
         *
         * @returns {jQuery}
         */
        getFallbackEl: function($el) {
            return $el.find(this.options.selectors.itemFallback).find('select');
        },

        /**
         * Get parent item code
         *
         * @param {jQuery} $item
         *
         * @returns {undefined|String}
         */
        getParentItemCode: function($item) {
            var select = this.getFallbackEl($item);
            if (select.length === 0 || select.attr('disabled')) {
                return;
            }

            var parentItemCode = select.attr('data-parent-locale');
            return parentItemCode && select.val() !== 'system' ? parentItemCode : select.val();
        },

        /**
         * Get item code
         *
         * @param {jQuery} $item
         *
         * @returns {String}
         */
        getItemCode: function($item) {
            var select = this.getFallbackEl($item);

            var itemCode = select.attr('data-locale');
            return itemCode ? itemCode : 'system';
        },

        fixFallbackWidth: function() {
            var maxWidth = 0;

            var $fallback = this.$el.find(this.options.selectors.itemFallback).find('div.selector').each(function() {
                var width = $(this).width();
                if (width > maxWidth) {
                    maxWidth = width;
                }
            });

            maxWidth -= 20;//minus arrow width

            $fallback.width(maxWidth)
                .find('span').width(maxWidth);
        }
    });

    return FallbackView;
});
