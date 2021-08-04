define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');
    const _ = require('underscore');
    const $ = require('jquery');

    const DefaultVariantCollectionView = BaseView.extend({
        $collection: null,

        options: {
            defaultSelector: '[name$="[default]"]',
            itemSelector: '[data-role="content-variant-item"]',
            itemContent: '[name$="[content]"]',
            activeItemSelector: '.content-variant-item-content.show',
            defaultItemClass: 'content-variant-item-default',
            defaultItemCollapseSelector: '.content-variant-item-default .collapse'
        },

        /**
         * @inheritdoc
         */
        events() {
            return {
                [`shown.bs.collapse ${this.options.itemSelector}`]: 'handleToggleCollapse',
                [`hide.bs.collapse ${this.options.itemSelector}`]: 'handleToggleCollapse',
                [`click ${this.options.defaultSelector}`]: event => this.onDefaultChange($(event.target))
            };
        },

        listen: {
            'cms:content-variant-collection:add mediator': 'handleChange',
            'cms:content-variant-collection:remove mediator': 'handleChange'
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
            this.handleChange();
            this.enableActiveItem();
            this.handleToggleCollapse = _.debounce(this.handleToggleCollapse, 300);
            DefaultVariantCollectionView.__super__.initialize.call(this, options);
        },

        handleToggleCollapse(event) {
            if (event.namespace !== 'bs.collapse') {
                return;
            }

            const target = $(event.currentTarget);
            target.find(this.options.itemContent).trigger(
                event.type === 'hide'
                    ? 'wysiwyg:disable'
                    : 'wysiwyg:enable'
            );
        },

        handleChange() {
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
        },

        enableActiveItem() {
            const activeItem = this.$el.find(this.options.activeItemSelector);
            if (activeItem.length) {
                activeItem.find(this.options.itemContent).trigger('wysiwyg:enable');
            }
        }
    });

    return DefaultVariantCollectionView;
});
