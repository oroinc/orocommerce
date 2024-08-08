import BaseView from 'oroui/js/app/views/base/view';
import ElementsHelper from 'orofrontend/js/app/elements-helper';
import $ from 'jquery';
import _ from 'underscore';
import NumberFormatter from 'orolocale/js/formatter/number';
import routing from 'routing';

const FrontendRequestProductModesView = BaseView.extend(_.extend({}, ElementsHelper, {
    elements: {
        editModeContainer: '[data-role="request-product-edit-mode-container"]',
        viewModeContainer: '[data-role="request-product-view-mode-container"]',
        viewMode: ['viewModeContainer', '[data-role="request-product-view-mode"]'],
        viewModeTemplate: ['$html', '#request-product-view-mode-template'],
        removeButton: '[data-role="remove"]',
        editButton: '[data-role="edit"]',
        updateButton: '[data-role="update"]',
        declineButton: '[data-role="decline"]'
    },

    modelEvents: {
        'productId onChangeProduct': ['change', 'onChangeProduct'],
        'mode onChangeMode': ['change', 'onChangeMode']
    },

    elementsEvents: {
        'editButton onClickEditButton': ['click', 'onClickEditButton'],
        'updateButton onClickUpdateButton': ['click', 'onClickUpdateButton'],
        'declineButton onClickDeclineButton': ['click', 'onClickDeclineButton']
    },

    /**
     * @property {Backbone.Model}
     */
    model: null,

    /**
     * @property {Backbone.Collection}
     */
    kitItemLineItems: null,

    /**
     * @property {Backbone.Collection}
     */
    requestProductItems: null,

    /**
     * @property {Function}
     */
    viewModeTemplate: null,

    /**
     * @property {Object}
     */
    savedAttributes: {},

    /**
     * @inheritdoc
     */
    constructor: function FrontendRequestProductModesView(options) {
        FrontendRequestProductModesView.__super__.constructor.call(this, options);
    },

    /**
     * @param {Object} options
     */
    initialize: function(options) {
        FrontendRequestProductModesView.__super__.initialize.call(this, options);

        this.initModel(options);
        this.initializeElements(options);

        this.viewModeTemplate = _.template(this.getElement('viewModeTemplate').text());

        this.listenTo(this.requestProductItems, 'remove', this.onRemoveRequestProductItem);
    },

    initModel: function(options) {
        this.model = options.requestProductModel;
        this.kitItemLineItems = options.kitItemLineItems;
        this.requestProductItems = options.requestProductItems;
    },

    onChangeProduct: function(data) {
        this.triggerOnCollectionItems(this.kitItemLineItems, 'state:softRemove');

        if (!this.model.get('productId')) {
            this.triggerOnCollectionItems(this.requestProductItems, 'state:softRemove');
        }
    },

    onChangeMode: function() {
        if (this.model.get('mode') === 'edit') {
            this.getElement('declineButton').text(_.__('oro.rfp.request.btn.cancel.label'));

            this.getElement('viewModeContainer').addClass('hidden');
            this.getElement('editModeContainer').removeClass('hidden');
        } else {
            this.renderViewMode().done(() => {
                this.getElement('editModeContainer').addClass('hidden');
                this.getElement('viewModeContainer').removeClass('hidden');
            });
        }
    },

    onRemoveRequestProductItem: function() {
        if (_.isEmpty(this.savedAttributes) &&
            !this.requestProductItems.length &&
            this.model.get('productId')) {
            this.remove();
        }
    },

    renderViewMode: function() {
        const deferred = $.Deferred();
        const viewMode = this.viewModeTemplate({
            routing: routing,
            numberFormatter: NumberFormatter,
            requestProduct: this.model.toJSON(),
            kitItemLineItems: _.map(this.kitItemLineItems.models, each => each.toJSON()),
            requestProductItems: _.map(this.requestProductItems.models, each => each.toJSON())
        });

        this.getElement('viewMode').html(viewMode);
        this.getElement('viewMode').one('content:initialized', () => {
            this.initializeSubviews({
                requestProductModel: this.model
            });

            deferred.resolve();
        });
        this.getElement('viewMode').trigger('content:changed');

        return deferred.promise();
    },

    remove: function() {
        this.getElement('removeButton').trigger('click');
    },

    onClickEditButton: function(e) {
        e.preventDefault();

        this.saveState();
        this.switchMode('edit');
    },

    onClickDeclineButton: function(e) {
        e.preventDefault();

        if (this.$el.find('.validation-failed').length) {
            return;
        }

        if (_.isEmpty(this.savedAttributes)) {
            this.remove();
            return;
        }

        this.revertState();
        this.switchMode('view');
    },

    onClickUpdateButton: function(e) {
        e.preventDefault();

        if (this.$el.find('.validation-failed').length) {
            return;
        }

        this.applyState();
        this.switchMode('view');
    },

    switchMode: function(mode) {
        this.model.set('mode', mode);
    },

    saveState: function() {
        this.savedAttributes = $.extend(
            true,
            {},
            _.omit(this.model.attributes, ['mode'])
        );

        this.triggerOnCollectionItems(this.requestProductItems, 'state:save');
    },

    revertState: function() {
        this.model.set(this.savedAttributes);

        this.triggerOnCollectionItems(this.kitItemLineItems, 'state:revert');
        this.triggerOnCollectionItems(this.requestProductItems, 'state:revert');
    },

    applyState: function() {
        this.savedAttributes = {};

        this.triggerOnCollectionItems(this.kitItemLineItems, 'state:apply');
        this.triggerOnCollectionItems(this.requestProductItems, 'state:apply');
    },

    /**
     * @param {Backbone.Collection} collection
     * @param {string} eventName
     */
    triggerOnCollectionItems: function(collection, eventName) {
        collection.trigger(eventName);

        // Collection is iterated in reverse order on purpose as may change during this loop.
        for (let i = collection.models.length - 1; i >= 0; i--) {
            collection.models[i].trigger(eventName);
        }
    }
}));

export default FrontendRequestProductModesView;
