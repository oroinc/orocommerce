import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseTypeBuilder from 'orocms/js/app/grapesjs/type-builders/base-type-builder';

const TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'ul', 'li', 'ol'];

const TextTypeBuilder = BaseTypeBuilder.extend({
    parentType: 'text',

    button: {
        label: __('oro.cms.wysiwyg.component.text.label'),
        content: {
            type: 'text',
            content: __('oro.cms.wysiwyg.component.text.content'),
            style: {
                'min-height': '18px'
            }
        }
    },

    constructor: function TextTypeBuilder(options) {
        TextTypeBuilder.__super__.constructor.call(this, options);
    },

    isComponent(el) {
        let _res = {
            tagName: el.tagName.toLowerCase()
        };

        if (TAGS.includes(_res.tagName)) {
            _res = {
                ..._res,
                type: 'text'
            };
        }

        return _res;
    },

    modelMixin: {
        tagUpdated() {
            if (!this.collection) {
                return;
            }
            const styles = this.getStyle();
            const at = this.collection.indexOf(this);
            const {rteEnabled} = this.view;

            if (rteEnabled) {
                this.view.disableEditing(false);
                this.editor.selectRemove(this);
            }

            this.constructor.__super__.tagUpdated.call(this);

            const model = this.collection.at(at);
            model.setStyle(styles);

            if (rteEnabled) {
                this.editor.selectToggle(model);
                _.defer(() => {
                    model.trigger('focus');
                    this.view.setCaretToStart();
                });
            }
        },

        replaceWith(el) {
            const styles = this.getStyle();
            const classes = this.getClasses();
            const newModels = this.constructor.__super__.replaceWith.call(this, el);

            newModels.forEach(model => {
                model.setStyle(styles);
                model.setClass(classes);
            });

            return newModels;
        }
    },

    viewMixin: {
        /**
         * Post process enter press
         * @param event
         * @returns {boolean}
         */
        onPressEnter(event) {
            if (event.keyCode !== 13) {
                return true;
            }

            const {activeRte} = this;
            let newEle = activeRte.doc.createTextNode('\n');
            const range = activeRte.doc.getSelection().getRangeAt(0);

            if (
                range.startContainer.nodeType === 3 &&
                range.endOffset <= range.commonAncestorContainer.length &&
                TAGS.includes(range.startContainer.parentNode.tagName.toLowerCase())
            ) {
                activeRte.doc.execCommand('defaultParagraphSeparator', true, 'p');
                return true;
            }

            if (activeRte.doc.queryCommandSupported('insertBrOnReturn')) {
                activeRte.doc.execCommand('defaultParagraphSeparator', false, 'br');
            }

            if (activeRte.doc.queryCommandSupported('insertLineBreak')) {
                activeRte.doc.execCommand('insertLineBreak', false, null);
                return false;
            }

            event.preventDefault();
            event.stopPropagation();

            const docFragment = activeRte.doc.createDocumentFragment();
            docFragment.appendChild(newEle);
            newEle = activeRte.doc.createElement('br');
            docFragment.appendChild(newEle);
            range.deleteContents();
            range.insertNode(docFragment);
            this.setCaretToStart(newEle);

            return false;
        },

        /**
         * Set cursor position
         * @param {Node} afterNode
         */
        setCaretToStart(afterNode = null) {
            const {activeRte, el} = this;
            const range = activeRte.doc.createRange();
            const sel = activeRte.doc.getSelection();

            if (afterNode) {
                range.setStartAfter(afterNode);
            } else {
                range.setStart(el, 0);
            }

            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);

            activeRte.updateActiveActions();
        },

        /**
         * Wrapping component from tag
         * @param tagName
         */
        wrapComponent(tagName = 'div') {
            const {model} = this;
            model
                .set('content', model.toHTML())
                .set('attributes', {})
                .set('tagName', tagName);
        },

        /**
         * Remove component wrapper
         */
        removeWrapper() {
            const [model] = this.model.replaceWith(this.getContent());
            this.editor.select(model);
        },

        /**
         * Is single line text block
         * @returns {boolean}
         */
        isSingleLine() {
            const comps = this.model.components();

            return this.model.get('tagName') === 'div' &&
                comps.length === 1 &&
                comps.at(0).get('type') !== 'textnode' &&
                TAGS.includes(comps.at(0).get('tagName'));
        },

        /**
         * Active RTE handler
         * @param {Event} e
         */
        onActive(event) {
            this.constructor.__super__.onActive.call(this, event);
            const {activeRte, $el, cid} = this;

            if (activeRte) {
                $el.on(`keypress.${cid}`, this.onPressEnter.bind(this));

                if (TAGS.includes(this.model.get('tagName'))) {
                    this.wrapComponent('div');
                }
            }
        },

        /**
         * Disable element content editing
         */
        disableEditing(clean = true) {
            const {model, rte, activeRte, em, $el, cid} = this;
            if (!model) {
                return;
            }

            const editable = model.get('editable');

            if (rte && editable) {
                $el.off(`keypress.${cid}`);

                try {
                    rte.disable(this, activeRte);
                } catch (err) {
                    em.logError(err);
                }

                this.syncContent();
            }

            this.toggleEvents();

            if (clean && this.isSingleLine()) {
                this.removeWrapper();
            }

            if (model.get('tagName') && this.getContent() === '') {
                model.set('content', __('oro.cms.wysiwyg.component.text.content'));
            }
        },

        updateContentText({model, ...args}) {
            if (!model) {
                return;
            }
            this.constructor.__super__.updateContentText.apply(this, [model, ...args]);
        },

        /**
         * Merge content from the DOM to the model
         * @param opts
         */
        syncContent(opts = {}) {
            const {model, rte, rteEnabled} = this;
            if (!rteEnabled && !opts.force) return;
            const content = this.getContent();
            const comps = model.components();
            const contentOpt = {
                fromDisable: false,
                previousModels: _.clone(comps),
                idUpdate: true,
                ...opts
            };
            comps.length && comps.reset(null, opts);
            model.set('content', '', contentOpt);

            // If there is a custom RTE the content is just baked staticly
            // inside 'content'
            if (rte.customRte) {
                model.set('content', content, contentOpt);
            } else {
                const clean = model => {
                    const textable = !!model.get('textable');
                    const selectable =
                        !['text', 'default', ''].some(type => model.is(type)) || textable;

                    model.set({
                        _innertext: false,
                        editable: selectable && model.get('editable'),
                        selectable: selectable,
                        hoverable: selectable,
                        removable: textable,
                        draggable: textable,
                        highlightable: 0,
                        copyable: textable,
                        ...(!textable && {toolbar: ''})
                    }, opts);

                    model.get('components').each(model => clean(model));
                };

                // Avoid re-render on reset with silent option
                !opts.silent && model.trigger('change:content', model, '', contentOpt);

                comps.add(content, opts);

                comps.each(model => clean(model));
                comps.trigger('resetNavigator');
            }
        }
    }
});

export default TextTypeBuilder;
