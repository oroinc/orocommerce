import {invoke, uniq} from 'underscore';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import template from 'tpl-loader!orocms/templates/plugins/rte-collection-view-template.html';
import RteItemView from './rte-item-view';

const RteCollectionView = BaseCollectionView.extend({
    optionNames: BaseCollectionView.prototype.optionNames.concat([
        'editableEl', '$editableEl', 'editor'
    ]),

    $editableEl: null,

    editor: null,

    template,

    /**
     * @inheritdoc
     */
    itemView: RteItemView,

    constructor: function RteCollectionView(options) {
        RteCollectionView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        RteCollectionView.__super__.initialize.call(this, options);
        this.doc = this.editableEl.ownerDocument;
    },

    /**
     * @inheritdoc
     * @param {DOM.Event} events
     * @returns {RteCollectionView}
     */
    delegateEvents: function(events) {
        RteCollectionView.__super__.delegateEvents.call(this, events);

        this.$editableEl.on(`mouseup${this.eventNamespace()} keyup${this.eventNamespace()}`,
            this.updateActiveActions.bind(this));

        return this;
    },

    /**
     * @inheritdoc
     * @returns {RteCollectionView}
     */
    undelegateEvents: function() {
        RteCollectionView.__super__.undelegateEvents.call(this);

        this.$editableEl.off(this.eventNamespace());

        return this;
    },

    /**
     * @inheritdoc
     * @returns {Object}
     */
    getTemplateData() {
        const data = RteCollectionView.__super__.getTemplateData.call(this);

        data['groups'] = uniq(this.collection.pluck('group'));

        return data;
    },

    /**
     * Rewrite method to put options for itemView
     * @param model
     * @returns {*}
     */
    initItemView(model) {
        if (this.itemView) {
            return new this.itemView({
                autoRender: false,
                actionbar: this.el,
                editableEl: this.editableEl,
                $editableEl: this.$editableEl,
                editor: this.editor,
                model
            });
        } else {
            throw new Error(
                'The CollectionView#itemView property must be defined or the initItemView() must be overridden.'
            );
        }
    },

    /**
     * Dynamically change list element for group actions
     * @param {Backbone.Model} item
     * @param {Backbone.View} view
     * @param args
     * @returns {*}
     */
    insertView(item, view, ...args) {
        this.$list = this.$(`[data-group-by="${view.model.get('group')}"]`);
        return RteCollectionView.__super__.insertView.apply(this, [item, view, ...args]);
    },

    /**
     * Define default list element
     * @returns {(Backbone.View|Array)}
     */
    renderAllItems() {
        const res = RteCollectionView.__super__.renderAllItems.call(this);

        this.$list = this.$el;

        invoke(this.subviews, 'onRender');
        return res;
    },

    /**
     * Update all action status
     */
    updateActiveActions() {
        invoke(this.subviews, 'updateActiveState');
    },

    /**
     * Focusing RTE editor element
     */
    focus() {
        this.$el.focus();
    },

    /**
     * Blur RTE editor element
     */
    blur() {
        this.$el.blur();
    }
});

export default RteCollectionView;
