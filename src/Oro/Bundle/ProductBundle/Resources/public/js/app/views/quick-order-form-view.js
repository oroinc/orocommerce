import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import QuickAddCollection from 'oroproduct/js/app/models/quick-add-collection';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import LoadingBarView from 'oroui/js/app/views/loading-bar-view';
import Progress from 'oroui/js/app/services/progress';

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
            [`content:initialized ${this.elem.rowsCollection}`]: 'onContentInitialized',
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
        'rows-initialization-progress': 'updateLoadingBarProgress',
        'quick-add-rows collection': 'onCollectionQuickAddRows',
        'quick-add-rows:before-load collection': 'checkRowsAvailability',
        'update collection': 'checkRowsQuantity'
    },

    constructor: function QuickOrderFromView(options) {
        QuickOrderFromView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        this.checkRowsQuantity = _.debounce(this.checkRowsQuantity.bind(this), 25);

        this.options = $.extend(true, {}, this.options, options);
        const collectionOptions = Object.assign({
            ajaxOptions: {
                global: false // ignore global loading bar
            }
        }, _.pick(this.options, 'productBySkuRoute'));
        this.collection = new QuickAddCollection([], collectionOptions);

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

    onCollectionQuickAddRows(requestPromise) {
        if (!this.subview('loadingMask')) {
            this.subview('loadingMask', new LoadingMaskView({
                container: this.$el
            }));
            this.subview('loadingBar', new LoadingBarView({
                container: this.$el,
                className: 'loading-bar loading-bar__actual-progress'
            }));
        }
        this.$el.attr('data-ignore-tabbable', '');
        this.$el.addClass('quick-order__progress');
        this.subview('loadingMask').show();
        this.subview('loadingBar').showLoader();

        const initPromise = new Promise(resolve => {
            this.once('rows-initialization-done', () => {
                resolve();
            });
        });
        Promise.all([requestPromise, initPromise]).finally(() => {
            this.subview('loadingBar').hideLoader(() => {
                this.$el.removeAttr('data-ignore-tabbable');
                this.$el.removeClass('quick-order__progress');
                this.subview('loadingMask').hide();
            });
        });
    },

    updateLoadingBarProgress(value) {
        if (this.subview('loadingBar')) {
            this.subview('loadingBar').setProgress(value);
        }
    },

    onContentInitialized() {
        if (this._initProgress) {
            this._initProgress.step();
        }
        this.checkRowsCount();
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
        } else {
            this.trigger('rows-initialization-done'); // no need for additional rows -- nothing to init
        }
    },

    async addRows(count) {
        const batchSize = this.options.rowsBatchSize;
        // progress contains steps for adding HTML and steps for initializing it
        const progress = this._initProgress = new Progress(Math.ceil(count / batchSize) * 2);
        this.listenTo(progress, 'progress', value => {
            this.trigger('rows-initialization-progress', value);
        });
        this.listenToOnce(progress, 'done', () => {
            this.stopListening(progress);
            delete this._initProgress;
            this.trigger('rows-initialization-done');
        });

        while (count > 0) {
            await window.sleep(0); // give time to repaint UI
            const batch = count > batchSize ? batchSize : count;
            this.$(this.elem.add).trigger({type: 'add-rows', count: batch});
            progress.step();
            count -= batch;
        }
    },

    getRowsCount() {
        return this.$(this.elem.rows).length;
    }
});

export default QuickOrderFromView;
