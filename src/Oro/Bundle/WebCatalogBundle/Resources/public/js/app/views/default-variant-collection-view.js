define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const $ = require('jquery');

    const DefaultVariantCollectionView = BaseView.extend({
        $collection: null,

        options: {
            defaultSelector: '[name$="[default]"]',
            itemSelector: '[data-role="content-variant-item"]',
            defaultItemClass: 'content-variant-item-default'
        },

        /**
         * @inheritdoc
         */
        constructor: function DefaultVariantCollectionView(options) {
            DefaultVariantCollectionView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});

            this.$el.on(
                'click',
                this.options.defaultSelector,
                e => {
                    this.onDefaultChange($(e.target));
                }
            );
            mediator.on('webcatalog:content-variant-collection:add', this.handleAdd, this);
            mediator.on('webcatalog:content-variant-collection:remove', this.handleRemove, this);

            this.handleAdd();
        },

        handleRemove: function($container) {
            // Check is default variant removed
            if ($container.find(this.options.defaultSelector + ':checked').length === 0) {
                this.checkDefaultVariant();
            }
        },

        handleAdd: function() {
            if (this.$el.find(this.options.itemSelector).length &&
                this.$el.find(this.options.defaultSelector + ':checked').length === 0
            ) {
                this.checkDefaultVariant();
            }
        },

        checkDefaultVariant: function() {
            const $default = this.$el.find(this.options.defaultSelector + ':not(:checked)').first();
            $default.prop('checked', true).trigger('change');

            this.onDefaultChange($default);
        },

        onDefaultChange: function($default) {
            this.$el.find('.' + this.options.defaultItemClass).removeClass(this.options.defaultItemClass);
            $default.closest(this.options.itemSelector).addClass(this.options.defaultItemClass);
        }
    });

    return DefaultVariantCollectionView;
});
