define(function(require) {
    'use strict';

    var DefaultVariantCollectionView;
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var $ = require('jquery');
    var _ = require('underscore');

    DefaultVariantCollectionView = BaseView.extend({
        $collection: {},
        $defaultSelector: '[name$="[default]"]',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.$collection = $(this.options.el);
            this.$defaultSelector = this.options.defaultSelector;

            if (this.$collection.children().length
                && this.$collection.find(this.$defaultSelector + ':checked').length == 0) {
                this.checkDefaultVariant();
            }

            mediator.on('webcatalog:content-variant-collection:add', this.handleAdd, this);
            this.$collection.on('content:remove', _.bind(this.handleRemove, this))
        },

        handleRemove: function(e) {
            // Check is default variant removed
            if ($(e.target).find(this.$defaultSelector + ':checked')) {
                this.checkDefaultVariant();
            }
        },

        handleAdd: function() {
            if (this.$collection.children().length
                && this.$collection.find(this.$defaultSelector + ':checked').length == 0) {
                this.checkDefaultVariant();
            }
        },
        
        checkDefaultVariant: function() {
            var $default = this.$collection.find(this.$defaultSelector + ':not(:checked)').first();
            $default.prop('checked', true).trigger('change');
        }

    });

    return DefaultVariantCollectionView;
});
