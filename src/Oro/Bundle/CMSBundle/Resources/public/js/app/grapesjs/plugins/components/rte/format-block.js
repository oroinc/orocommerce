import __ from 'orotranslation/js/translator';
import $ from 'jquery';
import 'jquery.select2';
import selectTemplate from 'tpl-loader!orocms/templates/grapesjs-select-action.html';
import select2OptionTemplate from 'tpl-loader!orocms/templates/grapesjs-select2-option.html';

const tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'];

const isBlockFormatted = node => node.nodeType === 1 && tags.includes(node.tagName.toLowerCase());

const surroundContent = (node, wrapper) => {
    if (node.nodeType !== 1) {
        return node;
    }
    wrapper.innerHTML = node.innerHTML;
    node.innerHTML = '';
    node.appendChild(wrapper);
};

const unwrap = node => {
    const parent = node.parentNode;
    while (node.firstChild) {
        parent.insertBefore(node.firstChild, node);
    }
    parent.removeChild(node);
};

function makeSurroundNode(context) {
    return (node, surround) => {
        const parent = context.createElement(surround);
        clearTextFormatting(node);
        surroundContent(node, parent);
    };
};

function clearTextFormatting(node) {
    if (node.childNodes.length) {
        node.childNodes.forEach(child => {
            if (isBlockFormatted(child)) {
                unwrap(child);
                clearTextFormatting(child);
            }
        });
    }
};

function findTextFormattingInRange(range, node = null) {
    return [...(node ? node.childNodes : range.commonAncestorContainer.childNodes)].reduce((tags, child) => {
        if (!range.intersectsNode(child)) {
            return tags;
        }

        if (isBlockFormatted(child)) {
            tags.push(child.tagName.toLowerCase());
        }

        return tags.concat(...findTextFormattingInRange(range, child));
    }, []);
};

function setCaretPosition(element, caretPos) {
    if (caretPos === 0) {
        return;
    }

    if (element !== null) {
        if (element.createTextRange) {
            const range = element.createTextRange();
            range.move('character', caretPos);
            range.select();
        } else {
            if (element.selectionStart) {
                element.focus();
                element.setSelectionRange(caretPos, caretPos);
            } else {
                element.focus();
            }
        }
    }
};

export default {
    name: 'formatBlock',

    icon: selectTemplate({
        options: {
            normal: __('oro.cms.wysiwyg.format_block.normal'),
            p: __('oro.cms.wysiwyg.format_block.p'),
            h1: __('oro.cms.wysiwyg.format_block.h1'),
            h2: __('oro.cms.wysiwyg.format_block.h2'),
            h3: __('oro.cms.wysiwyg.format_block.h3'),
            h4: __('oro.cms.wysiwyg.format_block.h4'),
            h5: __('oro.cms.wysiwyg.format_block.h5'),
            h6: __('oro.cms.wysiwyg.format_block.h6')
        },
        name: 'tag'
    }),

    event: 'change',

    attributes: {
        'title': __('oro.cms.wysiwyg.format_block.title'),
        'class': 'gjs-rte-action text-format-action'
    },

    order: 10,

    group: 'format-block',

    init(rte) {
        const $select = $(rte.actionbar.querySelector('[name="tag"]'));
        $select.inputWidget('create', 'select2', {
            initializeOptions: {
                minimumResultsForSearch: -1,
                dropdownCssClass: 'gjs-rte-select2-dropdown',
                formatResult: state => select2OptionTemplate({state})
            }
        });
    },

    result(rte) {
        const value = rte.actionbar.querySelector('[name="tag"]').value;
        const selection = rte.selection();
        const anchorOffset = selection.anchorOffset;
        const parentNode = selection.getRangeAt(0).startContainer.parentNode;
        const range = selection.getRangeAt(0);
        const surround = makeSurroundNode(rte.doc);
        const isTag = range.commonAncestorContainer.nodeType === 1;
        const isTextNode = range.commonAncestorContainer.nodeType === 3;

        const removeParent = parentNode => {
            const text = parentNode.innerHTML;
            parentNode.remove();
            rte.insertHTML(text);
            this.editor.trigger('change:canvasOffset');
        };

        if (value === 'normal') {
            if (isTag) {
                range.commonAncestorContainer.childNodes.forEach(node => {
                    if (range.intersectsNode(node)) {
                        clearTextFormatting(node);
                    }
                });

                this.editor.trigger('change:canvasOffset');
                setCaretPosition(rte.el, anchorOffset);
                return;
            }

            if (isTextNode) {
                removeParent(parentNode);
                setCaretPosition(rte.el, anchorOffset);
                return;
            }
        }

        if (!range.collapsed && isTag) {
            range.commonAncestorContainer.childNodes.forEach(node => {
                if (range.intersectsNode(node)) {
                    surround(node, value);
                }
            });
            range.setStartAfter(range.endContainer);
            selection.removeAllRanges();
            this.editor.trigger('change:canvasOffset');

            setCaretPosition(rte.el, anchorOffset);
            return;
        }

        if (isTextNode && !isBlockFormatted(range.commonAncestorContainer.parentNode)) {
            const newParent = rte.doc.createElement(value);
            const docFragment = rte.doc.createDocumentFragment();
            newParent.appendChild(range.commonAncestorContainer);
            docFragment.appendChild(newParent);
            range.deleteContents();
            range.insertNode(docFragment);
            range.setStartAfter(newParent);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            this.editor.trigger('change:canvasOffset');

            setCaretPosition(rte.el, anchorOffset);
            return;
        }

        this.editor.trigger('change:canvasOffset');
        setCaretPosition(rte.el, anchorOffset);
        return rte.exec('formatBlock', value);
    },

    update(rte, action) {
        const value = rte.doc.queryCommandValue(action.name);
        const select = rte.actionbar.querySelector('[name="tag"]');
        const selection = rte.doc.getSelection();
        if (selection.anchorNode) {
            const range = selection.getRangeAt(0);
            if (range.commonAncestorContainer.nodeType === 1) {
                const formatting = findTextFormattingInRange(range);
                if (formatting.length) {
                    $(select).select2('val', formatting[0]);
                    return;
                }
            }
        }

        if (value === '' && isBlockFormatted(rte.el)) {
            $(select).select2('val', rte.el.tagName.toLowerCase());
            return;
        }

        if (value !== 'false') {
            if (tags.includes(value)) {
                $(select).select2('val', value);
            } else {
                $(select).select2('val', 'normal');
            }
        }
    }
};
