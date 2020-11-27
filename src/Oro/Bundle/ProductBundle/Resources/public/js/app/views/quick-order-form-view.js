import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import QuickAddCollection from 'oroproduct/js/app/models/quick-add-collection';

const QuickOrderFromView = BaseView.extend({
    elem: {
        form: '[data-role="quick-order-add-container"] form',
        rowsCollection: '.js-item-collection',
        rows: '[data-name="field__name"]',
        buttons: '[data-role="quick-order-add-buttons"]',
        clear: '[data-role="quick-order-add-clear"]',
        add: '.add-list-item'
    },

    events() {
        return {
            [`content:initialized ${this.elem.rowsCollection}`]: 'checkRowsCount',
            [`click ${this.elem.clear}`]: 'clearRows'
        };
    },

    /**
     * @property {Object}
     */
    options: {
        rowsCountThreshold: 20,
        rowsBatchSize: 50
    },

    listen: {
        'quick-add:before-load collection': 'checkRowsAvailability',
        'update collection': 'checkRowsQuantity'
    },

    constructor: function QuickOrderFromView(options) {
        QuickOrderFromView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize(options) {
        this.checkRowsQuantity = _.debounce(this.checkRowsQuantity.bind(this), 25);

        this.options = $.extend(true, {}, this.options, options);
        this.collection = new QuickAddCollection([], _.pick(this.options, 'productBySkuRoute'));

        this.initLayout({
            productsCollection: this.collection
        }).then(() => {
            const items = this.collection.filter('sku').map(model => [model.cid, {sku: model.get('sku')}]);
            if (items.length) {
                this.collection.loadProductInfo(Object.fromEntries(items));
            }
        });

        this.checkRowsCount();
        this.rowsCountInitial = this.getRowsCount();
    },

    checkRowsCount() {
        const rowsCount = this.getRowsCount();
        if (rowsCount > this.options.rowsCountThreshold) {
            this.showTopButtons();
        } else if (rowsCount <= this.rowsCountInitial) {
            this.hideTopButtons();
        }
    },

    showTopButtons() {
        const $buttons = this.$(this.elem.buttons);
        const $form = this.$(this.elem.form);

        this.$(this.elem.clear).removeClass('hidden');
        this.$buttonsCopy = this.$buttonsCopy ? this.$buttonsCopy : $($buttons, $form).clone(true, true);
        this.$buttonsCopy.prependTo($form);
    },

    hideTopButtons() {
        if (!this.$buttonsCopy) {
            return;
        }
        this.$buttonsCopy.detach();
        this.$(this.elem.clear).addClass('hidden');
    },

    async clearRows() {
        const bathes = _.chunk(this.$(this.elem.rows), this.options.rowsBatchSize);
        await bathes.map(async rows => {
            await window.sleep(0);
            rows.forEach(rowElem => $(rowElem).trigger('content:remove').remove());
        });
        this.addRows(this.rowsCountInitial);
    },

    checkRowsQuantity(collection, options) {
        if (!this.disposed && this.collection.length < this.rowsCountInitial) {
            this.addRows(this.rowsCountInitial - this.collection.length);
        }
    },

    /**
     * Adds form rows for vacant models in collection
     */
    checkRowsAvailability() {
        const count = this.collection.filter(model => !model.has('_order')).length;
        if (count) {
            this.addRows(count);
        }
    },

    async addRows(count) {
        const batchSize = this.options.rowsBatchSize;
        while (count > 0) {
            await window.sleep(0); // give time to repaint UI
            const batch = count > batchSize ? batchSize : count;
            this.$(this.elem.add).trigger({type: 'add-rows', count: batch});
            count -= batch;
        }
    },

    getRowsCount() {
        return this.$(this.elem.rows).length;
    }
});

export default QuickOrderFromView;
