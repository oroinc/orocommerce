define(function(require) {
    'use strict';

    var RelatedItemsWidget;
    var DialogWidget = require('oro/dialog-widget');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');

    RelatedItemsWidget = DialogWidget.extend(_.extend({}, ElementsHelper, {
        listen: {
            contentLoad: 'onContentLoad'
        },

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            RelatedItemsWidget.__super__.initialize.apply(this, arguments);
        },

        onContentLoad: function() {
            $('[data-name="select-related-items"]').on('click', _.bind(this.onSelectRelatedItems, this));
        },

        prepareContentRequestOptions: function(data, method, url) {
            var options = RelatedItemsWidget.__super__.prepareContentRequestOptions.call(
                this, data, method, url
            );
            var addedProductRelatedIds = $(this.options.appendedRelatedIdsField).val();
            var removedProductRelatedIds = $(this.options.removedRelatedIdsField).val();

            options.data += '&' + $.param({
                    addedProductUpsell: addedProductUpsellIds,
                    removedProductUpsell: removedProductUpsellIds
                });

            return options;
        },

        onSelectRelatedItems: function() {
            var addedVal = $(this.options.appendedIdsField).val();
            var removedVal = $(this.options.removedIdsField).val();
            var appendedIds = addedVal.length ? addedVal.split(',') : [];
            var removedIds = removedVal.length ? removedVal.split(',') : [];

            mediator.trigger('product:save-related-items', appendedIds, removedIds);
            this.remove();
        }
    }));

    return RelatedItemsWidget;
});
