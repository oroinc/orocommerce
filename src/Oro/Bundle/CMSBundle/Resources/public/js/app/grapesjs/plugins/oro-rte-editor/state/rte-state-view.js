import {debounce} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import RteStateModel from './rte-state-model';
import {applyRange} from '../../components/rte/utils/utils';

const RteStateView = BaseView.extend({
    optionNames: ['doc', 'editor'],

    HISTORY_TIMEOUT: 200,

    events: {
        input: 'onInputDebounced',
        keydown: 'onKeydown'
    },

    constructor: function RteStateView(...args) {
        this.onInputDebounced = debounce(this.onInput.bind(this), this.HISTORY_TIMEOUT);
        RteStateView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        if (options.el.__cashData) {
            const {model: componentModel} = options.el.__cashData;

            this.model = new RteStateModel({
                componentModel,
                useOuterHTML: componentModel.get('wrapping')
            });
        } else {
            this.model = new RteStateModel();
        }

        this.model.set('content', this.el.innerHTML);

        RteStateView.__super__.initialize.call(this, options);
    },

    dispatchAction({keyCode, metaKey, shiftKey, ctrlKey}) {
        if ((ctrlKey || metaKey) && keyCode === 90) {
            return RteStateModel.UNDO;
        }

        if (((ctrlKey || metaKey) && shiftKey && keyCode === 90) || (ctrlKey || metaKey) && keyCode === 89) {
            return RteStateModel.REDO;
        }

        return false;
    },

    onKeydown(event) {
        const action = this.dispatchAction(event);

        if (action) {
            event.preventDefault();
            event.stopPropagation();

            this.model.execute(action);
            this.syncContent();
        }
    },

    syncContent() {
        const {content, range} = this.model.getState();

        this.el.innerHTML = content;

        applyRange(this.el, range, this.doc);
    },

    onInput() {
        if (this.model.get('content') !== this.$el.html()) {
            const {
                collapsed,
                startOffset,
                endOffset,
                startContainer,
                endContainer,
                commonAncestorContainer
            } = this.doc.getSelection().getRangeAt(0);

            this.model.set('range', {
                collapsed,
                startOffset,
                endOffset,
                startContainer: startContainer.cloneNode(),
                endContainer: endContainer.cloneNode(),
                commonAncestorContainer: commonAncestorContainer.cloneNode()
            });

            this.model.set('content', this.el.innerHTML);
        }
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.onInputDebounced.cancel();

        RteStateView.__super__.dispose.call(this);
    }
});

export default RteStateView;
