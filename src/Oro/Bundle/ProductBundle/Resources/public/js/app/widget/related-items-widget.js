define(function(require) {
    'use strict';

    var RelatedItemsWidget;
    var DialogWidget = require('oro/dialog-widget');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    RelatedItemsWidget = DialogWidget.extend(_.extend({}, ElementsHelper, {
        elements: {
            appendedIds: '[data-role="related-items-appended-ids"]',
            removedIds: '[data-role="related-items-removed-ids"]',
            selectButtonSelector: '[data-role="related-items-submit-button"]'
        },

        events: {
            'change .grid-body-cell-isRelated input': 'recalculateSelectedItemsCount'
        },

        listen: {
            contentLoad: 'onContentLoad',
            'datagrid:rendered mediator': 'setSelectedCount'
        },

        selectedCount: 0,

        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            RelatedItemsWidget.__super__.initialize.apply(this, arguments);

            this.options.itemsLimit = isNaN(this.options.itemsLimit) ? -1 : this.options.itemsLimit;

            this.initializeElements();
        },

        setSelectedCount: function(grid) {
            var selectedCount = grid.metadata.options.urlParams.relatedProducts;
            this.selectedCount = selectedCount !== 'undefined' ? selectedCount.length : 0;
        },

        onContentLoad: function() {
            this.clearElementsCache();
            this.initializeElements();

            this.recalculateSelectedItemsCount();

            $(this.elements.selectButtonSelector).on('click', _.bind(this.onSelectRelatedItems, this));
        },

        prepareContentRequestOptions: function(data, method, url) {
            var addedProductRelatedIds = $(this.options.itemsIdsToAdd).val();
            var removedProductRelatedIds = $(this.options.itemsIdsToRemove).val();

            var options = RelatedItemsWidget.__super__.prepareContentRequestOptions.apply(this, arguments);
            options.data += '&' + $.param({
                addedProductRelated: addedProductRelatedIds,
                removedProductRelated: removedProductRelatedIds
            });

            return options;
        },

        onSelectRelatedItems: function(e) {
            if ($(e.target).hasClass('disabled')) {
                return;
            }

            mediator.trigger('product:save-related-items', this.getAppendedIds(), this.getRemovedIds());
            this.remove();
        },

        recalculateSelectedItemsCount: function() {
            this.recalculatedRelatedItemsCount = this.selectedCount +
                this.getAppendedIds().length - this.getRemovedIds().length;

            this.updateUI();
        },

        getAppendedIds: function() {
            var addedVal = this.getElement('appendedIds').val();
            return addedVal.length ? addedVal.split(',') : [];
        },

        getRemovedIds: function() {
            var removedVal = this.getElement('removedIds').val();
            return removedVal.length ? removedVal.split(',') : [];
        },

        isLimitExceeded: function() {
            if (this.options.itemsLimit === -1 ||
                this.recalculatedRelatedItemsCount <= this.options.itemsLimit) {
                return false;
            }

            return true;
        },

        updateUI: function() {
            $(this.elements.selectButtonSelector)
                .toggleClass('btn-primary', !this.isLimitExceeded())
                .toggleClass('disabled', this.isLimitExceeded());
        },

        dispose: function() {
            delete this.selectedCount;
            delete this.recalculatedRelatedItemsCount;

            RelatedItemsWidget.__super__.dispose.call(this);
        }
    }));

    return RelatedItemsWidget;
});
