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
                padding: '10px'
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

    viewMixin: {
        onPressEnter(event) {
            if (event.keyCode !== 13) {
                return true;
            }

            const {activeRte} = this;
            const sel = activeRte.doc.getSelection();
            let newEle = activeRte.doc.createTextNode('\n');
            let range = activeRte.doc.getSelection().getRangeAt(0);

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
            range = activeRte.doc.createRange();
            range.setStartAfter(newEle);
            range.collapse(true);

            sel.removeAllRanges();
            sel.addRange(range);

            return false;
        },

        onActive(e) {
            this.constructor.__super__.onActive.call(this, e);
            const {activeRte, $el, cid} = this;

            if (activeRte) {
                $el.on(`keypress.${cid}`, this.onPressEnter.bind(this));
            }
        },

        /**
         * Disable element content editing
         */
        disableEditing() {
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
            const tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'];
            const {model, rte, rteEnabled} = this;
            if (!rteEnabled && !opts.force) return;
            let content = this.getContent();
            const comps = model.components();
            const contentOpt = {
                fromDisable: false,
                previousModels: _.clone(comps),
                idUpdate: true,
                ...opts
            };
            let tagName = null;
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

                const el = document.createElement('div');
                el.innerHTML = content;
                if (el.children.length === 1 &&
                    tags.includes(el.children[0].tagName.toLowerCase()) &&
                    tags.includes(model.get('tagName'))) {
                    tagName = el.children[0].tagName.toLowerCase();
                    content = el.children[0].innerHTML;
                }

                comps.add(content, opts);

                if (tagName) {
                    this.editor.selectRemove(model);
                    model.set('tagName', tagName);
                    model.trigger('focus');
                    rte.updatePosition();

                    this.editor.select(model);
                }

                comps.each(model => clean(model));
                comps.trigger('resetNavigator');
            }
        }
    }
});

export default TextTypeBuilder;
