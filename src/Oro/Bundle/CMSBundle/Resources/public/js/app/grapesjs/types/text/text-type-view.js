import __ from 'orotranslation/js/translator';
import {foundClosestParentByTagName} from 'orocms/js/app/grapesjs/plugins/components/rte/utils/utils';
import {TAGS} from './tags';

export default (BaseTypeView, {editor} = {}) => {
    const TextTypeView = BaseTypeView.extend({
        editor,

        constructor: function TextTypeView(...args) {
            return TextTypeView.__super__.constructor.apply(this, args);
        },

        /**
         * Post process enter press
         * @param event
         * @returns {boolean}
         */
        onPressEnter(event) {
            const {activeRte} = this;
            activeRte.emitEvent(event);

            if (event.keyCode === 9) {
                event.preventDefault();
            }

            if (event.keyCode !== 13) {
                return true;
            }

            let newEle = activeRte.doc.createTextNode('\n');
            const range = activeRte.doc.getSelection().getRangeAt(0);
            const container = range.commonAncestorContainer;
            const list = foundClosestParentByTagName(container, ['ul', 'ol'], true);

            if (list && !event.shiftKey) {
                return false;
            }

            if (
                range.startContainer.nodeType === Node.TEXT_NODE &&
                range.endOffset <= container.length &&
                TAGS.includes(range.startContainer.parentNode.tagName.toLowerCase())
            ) {
                activeRte.doc.execCommand('defaultParagraphSeparator', true, 'p');
                return true;
            }

            if (activeRte.doc.queryCommandSupported('insertBrOnReturn')) {
                activeRte.doc.execCommand('defaultParagraphSeparator', false, 'br');
                return true;
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
            const {model, em} = this;
            const parent = model.parent();
            const collection = parent.components();
            const index = model.index();
            const {
                marginTop,
                marginBottom,
                paddingTop,
                paddingBottom
            } = this.editor.Canvas.getWindow().getComputedStyle(this.el);

            const newModel = collection.add({
                type: 'text',
                wrapping: true,
                origin: false,
                stateModel: model.get('stateModel'),
                tagName
            }, {
                at: index
            });

            em.get('UndoManager').skip(() => model.set('draggable', false));
            model.move(newModel);

            newModel.view.$el.css({
                marginTop,
                marginBottom,
                paddingTop,
                paddingBottom
            });

            this.editor.select([newModel], {
                force: true
            });
            newModel.view.$el.trigger('dblclick');
        },

        /**
         * Remove component wrapper
         */
        removeWrapper() {
            const {model} = this;
            const index = model.index();
            const parent = model.parent();
            const child = model.getChildAt(0);
            this.pathChildModel(child, model.get('toolbar'));
            child.move(parent, {
                at: index
            });

            model.remove();
            model.view.$el.remove();

            this.editor.select([child], {
                wrapping: true
            });
        },

        pathChildModel(child, toolbar) {
            const {model} = this;

            child.set({
                layerable: true,
                selectable: true,
                hoverable: true,
                editable: true,
                draggable: true,
                droppable: true,
                highlightable: true
            });

            if (toolbar) {
                child.set('toolbar', toolbar);
            }

            if (model.get('stateModel')) {
                child.set('stateModel', model.get('stateModel'));
            }
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
         * @param {Event} event
         */
        async onActive(event) {
            if (this.model.parent().get('type') === 'text') {
                return;
            }

            if (TAGS.includes(this.model.get('tagName'))) {
                return this.em.get('UndoManager').skip(() => this.wrapComponent('div'));
            }

            await TextTypeView.__super__.onActive.call(this, event);
            const {activeRte, $el, cid} = this;

            this.propagatePropsToChildText({
                draggable: false
            });

            if (activeRte) {
                $el.off(`keydown.${cid}`).on(`keydown.${cid}`, this.onPressEnter.bind(this));
            }

            this.model.trigger('rte:enable:done');
        },

        /**
         * Disable element content editing
         */
        async disableEditing(opts) {
            const {$el, cid, em} = this;

            $el.off(`keypress.${cid}`);
            this.propagatePropsToChildText({
                draggable: true
            });

            await TextTypeView.__super__.disableEditing.call(this, opts);

            if (this.willRemoved) {
                return;
            }

            if (this.isSingleLine() && !this.model.get('origin')) {
                em.get('UndoManager').skip(() => this.removeWrapper());
            }
        },

        /**
         * Set some props to child components
         *
         * @param {object} props
         * @param {string} typeName
         */
        propagatePropsToChildText(props = {}, typeName = 'text') {
            this.model.findType(typeName).forEach(innerText => innerText.set(props));
        },

        remove(...args) {
            this.willRemoved = true;
            return TextTypeView.__super__.remove.apply(this, args);
        },

        /**
         * Merge content from the DOM to the model
         * @param opts
         */
        async syncContent({force, content = __('oro.cms.wysiwyg.component.text.content'), ...opts} = {}) {
            const {model, rteEnabled, willRemoved, em} = this;
            if ((!rteEnabled && !force) || willRemoved) {
                return;
            }

            em.get('UndoManager').skip(() => model.components().resetFromString(
                `<div data-type="temporary-container">${content}</div>`,
                opts
            ));
        }
    });

    return TextTypeView;
};
