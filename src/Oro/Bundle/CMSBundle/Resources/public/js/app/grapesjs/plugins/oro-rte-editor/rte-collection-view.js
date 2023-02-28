import {invoke, uniq} from 'underscore';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import template from 'tpl-loader!orocms/templates/plugins/rte-collection-view-template.html';
import RteItemView from './rte-item-view';
import {isBlockFormatted} from '../components/rte/utils/utils';

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

    observer: null,

    events: {
        click: 'updateActiveActions'
    },

    constructor: function RteCollectionView(options) {
        this.spansToRemove = [];
        this.spansToSave = [];

        RteCollectionView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        RteCollectionView.__super__.initialize.call(this, options);
        this.spansToSave = this.$editableEl[0].querySelectorAll('span');
        this.doc = this.editableEl.ownerDocument;
    },

    /**
     * @inheritdoc
     * @param {DOM.Event} events
     * @returns {RteCollectionView}
     */
    delegateEvents(events) {
        RteCollectionView.__super__.delegateEvents.call(this, events);

        this.$editableEl.on(`mouseup${this.eventNamespace()} keyup${this.eventNamespace()}`,
            this.updateActiveActions.bind(this));

        this.observer = new MutationObserver(this.onDOMMutation.bind(this));
        this.observer.observe(this.editableEl, {
            childList: true,
            subtree: true
        });

        return this;
    },

    /**
     * @inheritdoc
     * @returns {RteCollectionView}
     */
    undelegateEvents() {
        if (this.observer) {
            this.observer.disconnect();
        }

        RteCollectionView.__super__.undelegateEvents.call(this);

        this.$editableEl.off(this.eventNamespace());

        return this;
    },

    onDOMMutation(mutationsList) {
        for (const mutation of mutationsList) {
            const isSpanAdded = [...mutation.addedNodes].filter(node => {
                const isNotSavedSpan = ![...this.spansToSave].find(span => span.isEqualNode(node));
                return node.nodeType === Node.ELEMENT_NODE &&
                    node.tagName === 'SPAN' &&
                    isNotSavedSpan &&
                    node.getAttribute('data-gjs-type') !== 'text-style' &&
                    !isBlockFormatted(node.parentNode);
            });

            if (mutation.type === 'childList' && isSpanAdded.length) {
                this.spansToRemove.push(...isSpanAdded);
            }
        }
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
                model,
                collection: this
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

    dispose() {
        if (this.disposed) {
            return;
        }

        this.spansToRemove.forEach(span => span.replaceWith(...span.childNodes));
        this.spansToSave = [];
        this.spansToRemove = [];
        this.editableEl.normalize();
        RteCollectionView.__super__.dispose.call(this);

        this.editor.trigger('rte:disable');
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
    },

    emitEvent(event) {
        if (event.type === 'keydown') {
            invoke(this.subviews, 'onKeyDown', event);
        }
    }
});

export default RteCollectionView;
