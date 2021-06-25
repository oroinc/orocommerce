define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');

    const ContentVariantCollectionComponent = BaseComponent.extend({
        options: {
            buttonSelector: '[data-role="variant-button"]',
            variantRemoveSelector: '[data-action="remove"]',
            collectionContainerSelector: '[data-role="collection-container"]'
        },

        /**
         * @inheritdoc
         */
        constructor: function ContentVariantCollectionComponent(options) {
            ContentVariantCollectionComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;

            this.$el.on('click', this.options.buttonSelector, this.onAdd.bind(this));
            this.$el.on('click', this.options.variantRemoveSelector, this.onRemove.bind(this));
            this.prototypeName = this.$el.data('prototype-name') || '__name__';
            this.$collectionContainer = this.$el.find(this.options.collectionContainerSelector);
        },

        onAdd: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            if ($button.attr('disabled')) {
                return;
            }

            const prototype = this.$el.data('prototype-' + $button.data('content-variant-type-name'));
            if (prototype) {
                let index = parseInt(this.$el.data('last-index'));
                const nextItemHtml = prototype.replace(new RegExp(this.prototypeName, 'g'), index);

                this.$collectionContainer
                    .prepend(nextItemHtml)
                    .trigger('content:changed');
                this.$el.data('last-index', ++index);
            }

            mediator.trigger('webcatalog:content-variant-collection:add', this.$el);
        },

        onRemove: function(e) {
            e.preventDefault();
            const item = $(e.target).closest('*[data-content]');
            item.trigger('content:remove');
            item.remove();

            mediator.trigger('webcatalog:content-variant-collection:remove', this.$el);
        }
    });

    return ContentVariantCollectionComponent;
});
