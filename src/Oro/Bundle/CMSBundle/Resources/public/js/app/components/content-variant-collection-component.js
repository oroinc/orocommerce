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
            collectionContainerSelector: '[data-role="collection-container"]',
            firstCollectionItemSelector: '[data-role="content-variant-item"]:first .collapse',
            autoShowFirstItem: false
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
            this.showFirstItem(true);
        },

        onAdd: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            if ($button.attr('disabled')) {
                return;
            }

            const prototype = this.$el.data('prototype');
            if (prototype) {
                let index = parseInt(this.$el.data('last-index'));
                const nextItemHtml = prototype.replace(new RegExp(this.prototypeName, 'g'), index);

                this.$collectionContainer
                    .prepend(nextItemHtml)
                    .trigger('content:changed');
                this.$el.data('last-index', ++index);

                this.validateContainer();
            }

            mediator.trigger('cms:content-variant-collection:add', this.$el);
            this.showFirstItem(true);
        },

        onRemove: function(e) {
            e.preventDefault();
            const item = $(e.target).closest('*[data-content]');
            item.remove();

            mediator.trigger('cms:content-variant-collection:remove', this.$el);
            this.showFirstItem();
        },

        showFirstItem(force = false) {
            if (!this.options.autoShowFirstItem) {
                return;
            }

            if (force || !this.$collectionContainer.find('.show').length) {
                this.$collectionContainer.find(this.options.firstCollectionItemSelector).collapse('show');
            }
        },

        validateContainer: function() {
            const $validationField = this.$el.find('[data-name="collection-validation"]:first');
            const $form = $validationField.closest('form');
            if ($form.data('validator')) {
                $form.validate().element($validationField.get(0));
            }
        }
    });

    return ContentVariantCollectionComponent;
});
