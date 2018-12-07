define(function(require) {
    'use strict';

    var QuickAddView;
    var ElementsHelper = require('orofrontend/js/app/elements-helper');
    var BaseView = require('oroui/js/app/views/base/view');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');
    var $ = require('jquery');

    QuickAddView = BaseView.extend(_.extend({}, ElementsHelper, {
        elements: {
            container: '[data-role="quick-order-add-container"]',
            rows: '[data-name="field__name"]',
            displayName: '[data-name="field__product-display-name"]',
            sku: '[data-name="field__product-sku"]',
            unit: '[data-name="field__product-unit"]',
            buttons: '[data-role="quick-order-add-buttons"]',
            clear: '[data-role="quick-order-add-clear"]',
            remove: '[data-role="row-remove"]'
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
            rowsCountThreshold: 20
        },

        listen: {
            'quick-add-copy-paste-form:submit mediator': 'addQuickAddRows',
            'quick-add-import-form:submit mediator': 'addQuickAddRows'
        },

        newRows: [],
        existingRows: [],

        /**
         * @inheritDoc
         */
        constructor: function QuickAddView() {
            QuickAddView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options);
            QuickAddView.__super__.initialize.apply(this, arguments);

            this.initializeElements(options);
            this.rowsCountInitial = this.getRowsCount();
            this.checkRowsCount();
        },

        checkButtonsAndRows: function() {
            this.checkRowsCount();
            this.fillNewRowsWithData();
            this.updateRowsWithData();
        },

        checkRowsCount: function() {
            if (this.getRowsCount() > this.options.rowsCountThreshold) {
                this.showTopButtons();
            } else if (this.getRowsCount() <= this.rowsCountInitial) {
                this.hideTopButtons();
            }
        },

        showTopButtons: function() {
            var $buttons = this.getElement('buttons');
            var $container = this.getElement('container');

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
            _.each(data, function(rowData) {
                if (this.findRow(rowData)) {
                    this.existingRows.push(rowData);
                } else {
                    this.newRows.push(rowData);
                }
            }, this);

            if (this.getEmptyRows().length >= this.newRows.length) {
                this.fillNewRowsWithData();
                this.updateRowsWithData();
            }

            while (this.getEmptyRows().length < this.newRows.length) {
                $('.add-list-item').click();
            }
        },

        fillNewRowsWithData: function() {
            if (!this.newRows.length) {
                return;
            }

            var $rows = this.getEmptyRows();
            _.each(this.newRows, function(item, i) {
                mediator.trigger('quick-add-form:rows-ready', {item: item, $el: $rows.eq(i)});
            });

            this.newRows = [];
        },

        updateRowsWithData: function() {
            if (!this.existingRows.length) {
                return;
            }

            _.each(this.existingRows, function(item) {
                var $row = $(this.findRow(item));
                mediator.trigger('quick-add-form:rows-ready', {item: item, $el: $row});
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
            var rows = this.getElement('rows');
            return _.find(rows, function(row) {
                var $unit = $(row).find(this.elements.unit);
                var unitValue = $unit.val().toLowerCase();
                var unitLabel = $unit.find('option:selected').text().toLowerCase();
                var rowDataUnit = rowData.unit ? rowData.unit.toLowerCase() : '';

                return $(row).find(this.elements.sku).val() === rowData.sku &&
                    _.contains([unitLabel, unitValue], rowDataUnit);
            }, this);
        },

        getRowsCount: function() {
            this.clearElementsCache();
            return this.getElement('rows').length;
        },

        dispose: function() {
            delete this.rowsCountInitial;
            delete this.data;
            this.disposeElements();
            QuickAddView.__super__.dispose.apply(this, arguments);
        }
    }));

    return QuickAddView;
});
