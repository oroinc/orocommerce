define(function(require) {
    'use strict';

    var ContentVariantCollectionComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    ContentVariantCollectionComponent = BaseComponent.extend({
        options: {
            buttonSelector: '[data-role="variant-button"]',
            variantRemoveSelector: '[data-action="remove"]',
            collectionContainerSelector: '[data-role="collection-container"]'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);

            this.$el = this.options._sourceElement;

            this.$el.on('click', this.options.buttonSelector, _.bind(this.onAdd, this));
            this.$el.on('click', this.options.variantRemoveSelector, _.bind(this.onRemove, this));
            this.prototypeName = this.$el.data('prototype-name') || '__name__';
            this.$collectionContainer = this.$el.find(this.options.collectionContainerSelector);
        },

        onAdd: function(e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            if ($button.attr('disabled')) {
                return;
            }

            var prototype = this.$el.data('prototype-' + $button.data('content-variant-type-name'));
            if (prototype) {
                var index = parseInt(this.$el.data('last-index'));
                var nextItemHtml = prototype.replace(new RegExp(this.prototypeName, 'g'), index);

                this.$collectionContainer
                    .prepend(nextItemHtml)
                    .trigger('content:changed');
                this.$el.data('last-index', ++index);

                this.validateContainer();
            }

            mediator.trigger('webcatalog:content-variant-collection:add', this.$el);
        },

        onRemove: function(e) {
            e.preventDefault();
            var item = $(e.target).closest('*[data-content]');
            item.remove();

            mediator.trigger('webcatalog:content-variant-collection:remove', this.$el);
        },

        validateContainer: function() {
            var $validationField = this.$el.find('[data-name="collection-validation"]:first');
            var $form = $validationField.closest('form');
            if ($form.data('validator')) {
                $form.validate().element($validationField.get(0));
            }
        }
    });

    return ContentVariantCollectionComponent;
});
