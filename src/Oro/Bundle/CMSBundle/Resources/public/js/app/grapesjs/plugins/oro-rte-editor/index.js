import BaseClass from 'oroui/js/base-class';
import RteCollection from './rte-collection';
import rteActions from '../components/rte';
import RteCollectionView from './rte-collection-view';

function getTextNodesIn(element) {
    let textNodes = [];

    if (element) {
        for (const node of [...element.childNodes]) {
            if (node.nodeType === Node.TEXT_NODE) {
                textNodes.push(node);
            } else {
                textNodes = textNodes.concat(getTextNodesIn(node));
            }
        }
    }

    return textNodes;
}

function selectElementContents(el, doc) {
    const range = doc.createRange();
    const node = getTextNodesIn(el)[0];
    if (node) {
        range.selectNodeContents(node);
    }
    const sel = doc.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);
}

const GrapesjsRteEditor = BaseClass.extend({
    constructor: function GrapesjsRteEditor(...args) {
        GrapesjsRteEditor.__super__.constructor.apply(this, args);
    },

    initialize({editor, ...args}) {
        this.editor = editor;
        const {RichTextEditor} = this.editor;

        this.collection = new RteCollection(rteActions, {
            rte: RichTextEditor
        });

        this.listenTo(this.editor, 'destroy', this.onDestroy.bind(this));

        this.removeDefaultAllActions();
        this.defineRteEditor();

        GrapesjsRteEditor.__super__.initialize.apply(this, args);
    },

    /**
     * Remove default RTE actions from GrapesJS
     */
    removeDefaultAllActions() {
        const {RichTextEditor} = this.editor;

        for (const {name} of [...RichTextEditor.actions]) {
            RichTextEditor.remove(name);
        }
    },

    /**
     * Define custom RTE editor view
     */
    defineRteEditor() {
        const {RichTextEditor, Canvas, $} = this.editor;
        const self = this;
        this.editor.setCustomRte({
            enable(el, rte) {
                if (rte && !rte.disposed) {
                    this.focus(el, rte);
                    return rte;
                }

                el.contentEditable = true;

                rte = self.view = new RteCollectionView({
                    container: RichTextEditor.toolbar,
                    editableEl: el,
                    $editableEl: $(el),
                    editor: self.editor,
                    doc: Canvas.getDocument(),
                    collection: self.collection
                });

                this.focus(el, rte);

                return rte;
            },

            disable(el, rte) {
                el.contentEditable = false;

                if (rte && !rte.disposed) {
                    rte.dispose();
                }
            },

            focus(el, rte) {
                if (rte && rte.disposed) {
                    return;
                }

                el.contentEditable = true;

                rte && rte.focus();
                selectElementContents(el, rte.doc);
                rte.updateActiveActions();

                self.editor.trigger('canvas:refresh');
            },

            toggleVisibility(hidden = true) {
                self.view.$el.css('visibility', hidden ? 'hidden' : '');
            },

            getContent(el, rte) {
                if (rte) {
                    return rte.getContent();
                }

                return el.innerHTML;
            }
        });
    },

    /**
     * Add action to custom RTE editor
     * @param {Object} action
     */
    addAction(action) {
        this.collection.add(action);
    },

    /**
     * Remove action to custom RTE editor
     * @param {string} name
     */
    removeAction(name) {
        const found = this.collection.findWhere({name});

        if (found) {
            this.collection.remove(found);
        }
    },

    onDestroy() {
        if (this.view) {
            this.view.dispose();
        }
        this.collection.dispose();

        delete this.view;
        delete this.collection;
        delete this.editor;

        this.dispose();
    }
});

export default editor => {
    const {UndoManager} = editor;

    const setContent = (component, content, {
        useOuterHTML = false,
        selected = false,
        sync = true,
        ...options
    } = {}) => {
        const {editor} = component;
        if (useOuterHTML) {
            const stateModel = component.get('stateModel');

            const [model] = component.replaceWith(content, {
                updateStyle: false
            });

            model.set('stateModel', stateModel);
            if (selected) {
                editor.select([model]);
            }

            return;
        } else {
            component.components(content, options);
        }

        if (selected) {
            editor.select([component]);
        }

        if (sync && typeof component.syncContent() === 'function') {
            component.syncContent();
        }
    };

    // Extend Undo/Redo flow in editor UndoManager
    const originalUndo = UndoManager.undo;
    const originalRedo = UndoManager.redo;

    /**
     * Compare couple ids and detect if equal or it is match incremented id pattern `origin_id-{increment}`
     * @param {string} id
     * @param {string} compareId
     * @return {boolean}
     */
    const compareIncrementIds = (id, compareId) => {
        return id === compareId || new RegExp(`(${compareId})-([\\d]+)`, 'g').test(id);
    };

    editor.on('component:selected', (component, opts) => {
        if (opts.wrapping && component.get('stateModel')) {
            component.get('stateModel').set('useOuterHTML', true);
        }

        if (opts.fromLayers && opts.useValid) {
            component.unset('stateModel');
        }
    });

    editor.on('component:mount', component => {
        const parent = component.parent();

        if (!parent) {
            return;
        }

        const [origin, ...duplicates] = parent
            .components()
            .filter(({ccid}) => component.get('stateModel') && compareIncrementIds(component.ccid, ccid))
            .reverse();

        if (duplicates.length) {
            UndoManager.skip(() => {
                const originId = duplicates[0].getId();
                duplicates.forEach(d => d.remove());
                origin.setId(originId);
            });
        }

        if (parent.get('wrapping')) {
            const index = parent.index();
            const prev = parent.collection.at(index - 1);

            if (compareIncrementIds(component.ccid, prev.ccid)) {
                UndoManager.skip(() => {
                    parent.remove();
                    editor.select(prev);
                });
            }
        }
    });

    editor.once('destroy', () => {
        editor.off('component:selected');
        editor.off('component:mount');
    });

    UndoManager.undo = function(...args) {
        const selected = editor.getSelected();

        if (selected && selected.get('stateModel')) {
            const stateModel = selected.get('stateModel');

            if (stateModel.undo()) {
                return UndoManager.skip(() => setContent(
                    selected,
                    stateModel.getState().content,
                    {
                        useOuterHTML: stateModel.get('useOuterHTML'),
                        selected: true
                    })
                );
            } else {
                UndoManager.skip(() => editor.selectRemove(selected));
                return originalUndo.apply(this, args);
            }
        }

        return originalUndo.apply(this, args);
    };

    UndoManager.redo = function(...args) {
        const selected = editor.getSelected();

        if (selected && selected.get('stateModel')) {
            const stateModel = selected.get('stateModel');

            if (stateModel.redo()) {
                return UndoManager.skip(() => setContent(
                    selected,
                    stateModel.getState().content,
                    {
                        useOuterHTML: stateModel.get('useOuterHTML'),
                        selected: true
                    })
                );
            } else {
                UndoManager.skip(() => editor.selectRemove(selected));
                return originalRedo.apply(this, args);
            }
        }
        return originalRedo.apply(this, args);
    };

    editor.RteEditor = new GrapesjsRteEditor({editor});
};

