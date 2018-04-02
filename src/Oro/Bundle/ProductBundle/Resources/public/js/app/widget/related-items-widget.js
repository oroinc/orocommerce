define(function(require) {
    'use strict';

    var RelatedItemsWidget;
    var DialogWidget = require('oro/dialog-widget');
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var $ = require('jquery');

    RelatedItemsWidget = DialogWidget.extend(_.extend({}, ElementsHelper, {
        elements: {
            appendedIds: '[data-role="related-items-appended-ids"]',
            removedIds: '[data-role="related-items-removed-ids"]',
            selectButtonSelector: '[data-role="related-items-submit-button"]',
            limitError: '[data-role="related-items-limit-error"]',
            widgetActions: '[data-section="adopted"]'
        },

        events: {
            'change .grid-body-cell-isRelated input': 'recalculateSelectedItemsCount'
        },

        listen: {
            'contentLoad': 'onContentLoad',
            'datagrid:rendered mediator': 'setSelectedCount'
        },

        selectedCount: 0,

        /**
         * @inheritDoc
         */
        constructor: function RelatedItemsWidget() {
            RelatedItemsWidget.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            RelatedItemsWidget.__super__.initialize.apply(this, arguments);

            this.options.itemsLimit = isNaN(this.options.itemsLimit) ? -1 : this.options.itemsLimit;

            this.initializeElements();
        },

        setSelectedCount: function(grid) {
            var selectedCount = grid.metadata.options.urlParams.relatedItemsIds;
            this.selectedCount = selectedCount !== 'undefined' ? selectedCount.length : 0;
        },

        onContentLoad: function() {
            this.clearElementsCache();
            this.initializeElements();
            this.prepareError();

            this.recalculateSelectedItemsCount();

            $(this.elements.selectButtonSelector).on('click', _.bind(this.onSelectRelatedItems, this));
        },

        prepareContentRequestOptions: function(data, method, url) {
            var addedProductRelatedItemsIds = $(this.options.itemsIdsToAdd).val();
            var removedProductRelatedItemsIds = $(this.options.itemsIdsToRemove).val();

            var options = RelatedItemsWidget.__super__.prepareContentRequestOptions.apply(this, arguments);
            options.data += '&' + $.param({
                addedProductRelatedItems: addedProductRelatedItemsIds,
                removedProductRelatedItems: removedProductRelatedItemsIds
            });

            return options;
        },

        onSelectRelatedItems: function(e) {
            if ($(e.target).hasClass('disabled')) {
                return;
            }

            mediator.trigger('change:' + this.options.gridName, this.getAppendedIds(), this.getRemovedIds());
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

            $(this.elements.limitError).toggle(this.isLimitExceeded());
        },

        prepareError: function() {
            if (this.options.limitErrorTemplate !== undefined) {
                return;
            } else {
                this.options.limitErrorTemplate = _.template(
                    '<span class="pull-left validation-failed" <%= dataAttr %>><%= msg %></span>'
                );
            }

            $(this.elements.widgetActions).prepend(this.options.limitErrorTemplate({
                dataAttr: this.elements.limitError.replace(new RegExp(/\[|]/g), ''),
                msg: __('oro.product.widgets.select_related_items.limit_has_been_reached')
            }));
        },

        dispose: function() {
            delete this.selectedCount;
            delete this.recalculatedRelatedItemsCount;

            RelatedItemsWidget.__super__.dispose.call(this);
        }
    }));

    return RelatedItemsWidget;
});
