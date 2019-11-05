define(function(require) {
    'use strict';

    const ElementsHelper = require('orofrontend/js/app/elements-helper');
    const BaseView = require('oroui/js/app/views/base/view');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const _ = require('underscore');
    const $ = require('jquery');

    const QuickAddView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            container: '[data-role="quick-order-add-container"]',
            collection: '.js-item-collection',
            rows: '[data-name="field__name"]',
            displayName: '[data-name="field__product-display-name"]',
            sku: '[data-name="field__product-sku"]',
            unit: '[data-name="field__product-unit"]',
            buttons: '[data-role="quick-order-add-buttons"]',
            clear: '[data-role="quick-order-add-clear"]',
            remove: '[data-role="row-remove"]',
            add: '.add-list-item'
        },

        elementsEvents: {
            clear: ['click', 'clearRows']
        },

        events: {
            'content:initialized .js-item-collection': 'checkButtonsAndRows'
        },

        /**
         * @property {Object}
         */
        options: {
            rowsCountThreshold: 20,
            productBySkuRoute: 'oro_frontend_autocomplete_search'
        },

        listen: {
            'quick-add-copy-paste-form:submit mediator': 'addQuickAddRows',
            'quick-add-import-form:submit mediator': 'addQuickAddRows',
            'quick-add-copy-paste-form:process-complete mediator': 'onQuickAddRowsComplete'
        },

        newRows: [],
        existingRows: [],
        rowsPromise: null,

        /**
         * @inheritDoc
         */
        constructor: function QuickAddView(options) {
            this.onCollectionItemRemove = _.debounce(this.onCollectionItemRemove, 0);
            this.onQuickAddRowsComplete = _.debounce(this.onQuickAddRowsComplete, 0);

            QuickAddView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            QuickAddView.__super__.initialize.call(this, options);

            this.initializeElements(options);
            this.rowsCountInitial = this.getRowsCount();
            this.checkRowsCount();
        },

        checkButtonsAndRows: function() {
            this.checkRowsCount();
            this.fillNewRowsWithData();
            this.updateRowsWithData();
            if (this.rowsPromise) {
                this.rowsPromise.resolve();
            }
        },

        checkRowsCount: function() {
            if (this.getRowsCount() > this.options.rowsCountThreshold) {
                this.showTopButtons();
            } else if (this.getRowsCount() <= this.rowsCountInitial) {
                this.hideTopButtons();
            }
        },

        showTopButtons: function() {
            const $buttons = this.getElement('buttons');
            const $container = this.getElement('container');

            this.getElement('clear').removeClass('hidden');
            this.$buttonsCopy = this.$buttonsCopy ? this.$buttonsCopy : $($buttons, $container).clone(true, true);
            this.$buttonsCopy.prependTo($container);
        },

        hideTopButtons: function() {
            if (!this.$buttonsCopy) {
                return;
            }
            this.$buttonsCopy.detach();
            this.getElement('clear').addClass('hidden');
        },

        clearRows: function() {
            mediator.trigger('quick-add-form:clear');

            this.getElement('rows').eq(this.rowsCountInitial - 1).nextAll().find(
                this.getElement('remove')
            ).click();
            this.checkRowsCount();
        },

        addQuickAddRows: function(data) {
            this.rowsCountBeforeQuickAdd = this.getRowsCount();
            this.getElement('collection').on('content:remove' + this.eventNamespace(),
                this.onCollectionItemRemove.bind(this));

            this.rowsPromise = $.Deferred();
            _.each(data, function(rowData) {
                if (this.findRow(rowData)) {
                    this.existingRows.push(rowData);
                } else {
                    this.newRows.push(rowData);
                }
            }, this);

            const emptyRowLength = this.getEmptyRows().length;

            if (emptyRowLength >= this.newRows.length) {
                this.fillNewRowsWithData();
                this.updateRowsWithData();
                this.rowsPromise.resolve();
            } else {
                this.addRows(this.newRows.length - emptyRowLength);
            }

            if (data.length) {
                this.validateData(data);
            }
        },

        onCollectionItemRemove: function() {
            const rowsNeeded = this.rowsCountBeforeQuickAdd - this.getRowsCount();

            if (rowsNeeded > 0) {
                this.addRows(rowsNeeded);
            }
        },

        onQuickAddRowsComplete: function() {
            this.getElement('collection').off('content:remove' + this.eventNamespace());
            delete this.rowsCountBeforeQuickAdd;
        },

        validateData: function(data) {
            const val = _.pluck(data, 'sku');
            const routeParams = {
                name: 'oro_product_visibility_limited_with_prices',
                per_page: val.length,
                query: ''
            };

            const ajaxPromise = $.ajax({
                url: routing.generate(this.options.productBySkuRoute, routeParams),
                method: 'post',
                data: {
                    sku: val
                }
            });

            const requestId = _.uniqueId('request');
            mediator.trigger('quick-add-form:requestProductsBySku', {requestId: requestId});
            const self = this;
            $.when(ajaxPromise, this.rowsPromise)
                .done(function(ajaxPromiseArguments) {
                    if (ajaxPromiseArguments[1] === 'success') {
                        mediator.trigger('autocomplete:validate-response', ajaxPromiseArguments[0], requestId);
                        self.rowsPromise = null;
                        mediator.trigger('quick-add-form:successProductsBySku', {requestId: requestId});
                    } else {
                        mediator.trigger('quick-add-form:failProductsBySku', {requestId: requestId});
                    }
                })
                .fail(function() {
                    mediator.trigger('quick-add-form:failProductsBySku', {requestId: requestId});
                });
        },

        fillNewRowsWithData: function() {
            if (!this.newRows.length) {
                return;
            }

            const $rows = this.getEmptyRows();
            _.each(this.newRows, function(item, i) {
                mediator.trigger('quick-add-form-row:update', {item: item, $el: $rows.eq(i), triggerBlurEvent: false});
            });

            this.newRows = [];
        },

        updateRowsWithData: function() {
            if (!this.existingRows.length) {
                return;
            }

            _.each(this.existingRows, function(item) {
                const $row = $(this.findRow(item));
                mediator.trigger('quick-add-form-row:update', {item: item, $el: $row, triggerBlurEvent: false});
            }, this);

            this.existingRows = [];
        },

        getEmptyRows: function() {
            this.clearElementsCache();
            return this.getElement('rows').filter(_.bind(function(index, row) {
                return !$(row).find(this.elements.displayName).val();
            }, this));
        },

        findRow: function(rowData) {
            this.clearElementsCache();

            const rows = this.getElement('rows');
            const rowDataUnit = rowData.unit ? rowData.unit.toLowerCase() : '';

            return _.find(rows, function(row) {
                const $unit = $(row).find(this.elements.unit);
                const unitLabel = $unit.find('option:selected').text().toLowerCase();

                return unitLabel === rowDataUnit && $(row).find(this.elements.sku).val() === rowData.sku;
            }, this);
        },

        getRowsCount: function() {
            this.clearElementsCache();
            return this.getElement('rows').length;
        },

        addRows: function(count) {
            const $collectionElement = this.getElement('collection');
            const oldCount = $collectionElement.data('row-count-add');

            $collectionElement.data('row-count-add', count);
            this.getElement('add').click();
            $collectionElement.data('row-count-add', oldCount);
        },

        dispose: function() {
            delete this.rowsCountInitial;
            delete this.data;
            this.disposeElements();
            QuickAddView.__super__.dispose.call(this);
        }
    }));

    return QuickAddView;
});
