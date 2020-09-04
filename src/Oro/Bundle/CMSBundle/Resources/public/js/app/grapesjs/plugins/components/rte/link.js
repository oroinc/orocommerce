import __ from 'orotranslation/js/translator';

export default {
    name: 'link',

    order: 20,

    group: 'text-style',

    icon: '<span class="fa fa-link" aria-hidden="true"></span>',

    attributes: {
        'title': __('oro.cms.wysiwyg.component.link.label'),
        'class': 'gjs-rte-action link-format'
    },

    result(rte, action) {
        const {editor} = this;
        const selection = rte.selection();

        if (action.isSelectionALink(selection)) {
            const linkElement = action.getLinkElement(selection);
            const textNode = document.createTextNode(linkElement.innerText);

            selection.removeAllRanges();
            document.getSelection().removeAllRanges();

            linkElement.replaceWith(textNode);
        } else {
            rte.exec('createLink', ' ');

            const link = action.getLinkElement(rte.selection());

            editor.runCommand('open-create-link-dialog', {link: link});
        }
    },

    update(rte, action) {
        const selection = rte.selection();

        action.btn.firstChild.classList.toggle('unlink', action.isSelectionALink(selection));

        if (action.isSelectionALink(selection)) {
            action.btn.classList.add(rte.classes.active);
        }
    },

    isSelectionALink(selection) {
        if (!selection.anchorNode) {
            return false;
        }

        const parentNode = selection.anchorNode.parentNode;

        return parentNode && parentNode.nodeName === 'A';
    },

    getLinkElement(selection) {
        let linkElement = selection.anchorNode;

        if (linkElement.parentElement && linkElement.parentElement.tagName === 'A') {
            linkElement = linkElement.parentElement;
        } else {
            linkElement = linkElement.nextSibling;
        }

        return linkElement;
    }
};
