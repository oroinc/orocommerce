import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import ProductKitInShoppingListWidget from 'oro/product-kit-in-shopping-lists-widget';
import routing from 'routing';
import $ from 'jquery';
import _ from 'underscore';

const ProductKitInShoppingListsView = BaseView.extend(_.extend({}, ElementsHelper, {
    events: {
        click: 'onClick'
    },

    constructor: function ProductKitInShoppingListView(options) {
        ProductKitInShoppingListView.__super__.constructor.call(this, options);
    },

    initialize: function(options) {
        ProductKitInShoppingListsView.__super__.initialize.call(this, options);
        this.deferredInitializeCheck(options, ['productModel']);
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        ProductKitInShoppingListsView.__super__.dispose.call(this);
    },

    deferredInitialize: function(options) {
        this.options = $.extend(true, {}, this.options, _.pick(options, _.keys(this.options)));

        this.initModel(options);
    },

    initModel: function(options) {
        const modelAttr = _.each(options.modelAttr, (value, attribute) => {
            options.modelAttr[attribute] = value === 'undefined' ? undefined : value;
        }) || {};

        this.modelAttr = $.extend(true, {}, this.modelAttr, modelAttr);
        if (options.productModel) {
            this.model = options.productModel;
        }

        if (!this.model) {
            return;
        }

        _.each(this.modelAttr, (value, attribute) => {
            if (!this.model.has(attribute) || modelAttr[attribute] !== undefined) {
                this.model.set(attribute, value);
            }
        }, this);
    },

    /**
     * @param {jQuery.Event} event
     */
    onClick: function(event) {
        event.preventDefault();

        const $button = $(event.currentTarget);
        if ($button.data('disabled')) {
            return false;
        }

        const url = $button.data('route');
        const urlOptions = {
            productId: this.model.get('id')
        };

        this.subview('popup', new ProductKitInShoppingListWidget({
            url: routing.generate(url, urlOptions),
            model: this.model
        }));

        this.subview('popup').render();
    }
}));

export default ProductKitInShoppingListsView;
