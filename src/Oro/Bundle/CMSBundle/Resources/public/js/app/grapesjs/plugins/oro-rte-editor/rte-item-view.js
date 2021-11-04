import BaseView from 'oroui/js/app/views/base/view';

const btnState = {
    ACTIVE: 1,
    INACTIVE: 0,
    DISABLED: -1
};

const RteItemView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat([
        'rte', 'editableEl', 'actionbar', 'editor'
    ]),

    editor: null,

    editableEl: null,

    events() {
        return {
            [`${this.model.get('event')}`]: 'onAction'
        };
    },

    constructor: function RteItemView(options) {
        RteItemView.__super__.constructor.call(this, options);
    },

    render() {
        if (!this.elementMounted()) {
            return;
        }

        const {model} = this;
        this.$el
            .addClass(model.getClass('button'))
            .attr(model.get('attributes'))
            .append(model.get('icon'));

        return this;
    },

    onRender() {
        const init = this.model.get('init');
        if (init && typeof init === 'function') {
            init(this.getRteParams());
        }
    },

    getDoc() {
        return this.editableEl.ownerDocument;
    },

    elementMounted() {
        return this.getDoc().body.contains(this.editableEl);
    },

    /**
     * Collect RTE params
     */
    getRteParams() {
        const doc = this.getDoc();

        return {
            el: this.editableEl,
            doc,
            actionbar: this.actionbar,
            classes: this.model.get('classes'),
            selection() {
                return this.doc.getSelection();
            },
            exec(name, value = null) {
                doc.execCommand(name, false, value);
            },
            insertHTML: this.insertHTML.bind(this)
        };
    },

    /**
     * Set custom HTML to the selection, useful as the default 'insertHTML' command
     * doesn't work in the same way on all browsers
     * @param  {string} value HTML string
     */
    insertHTML(value) {
        const doc = this.getDoc();
        const selection = doc.getSelection();

        if (selection && selection.rangeCount) {
            const node = doc.createElement('div');
            const range = selection.getRangeAt(0);
            range.deleteContents();
            node.innerHTML = value;
            [...node.childNodes].forEach(nd => {
                range.insertNode(nd);
            });

            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
        }
    },

    /**
     * Execute action when click on button
     */
    onAction() {
        const doc = this.getDoc();
        if (this.model.get('result')) {
            this.model.get('result').call(this, this.getRteParams(), {
                ...this.model.attributes,
                btn: this.el
            });
        }

        if (this.model.get('command')) {
            doc.execCommand(this.model.get('command'), false, null);
        }

        this.updateActiveState();
    },

    /**
     * Update action button state
     */
    updateActiveState() {
        const {model} = this;
        const doc = this.editableEl.ownerDocument;
        const state = model.get('state');
        const update = model.get('update');
        const name = model.get('name');

        this.el.className = model.getClass('button');

        if (state) {
            switch (state(this, doc)) {
                case btnState.ACTIVE:
                    this.$el.addClass(model.getClass('active'));
                    break;
                case btnState.INACTIVE:
                    this.$el.addClass(model.getClass('inactive'));
                    break;
                case btnState.DISABLED:
                    this.$el.addClass(model.getClass('disabled'));
                    break;
            }
        } else {
            if (doc.queryCommandSupported(name) && doc.queryCommandState(name)) {
                this.$el.addClass(model.getClass('active'));
            }
        }

        if (update && typeof update === 'function') {
            update(this.getRteParams(), {
                ...this.model.attributes,
                btn: this.el
            });
        }
    }
});

export default RteItemView;
