import __ from 'orotranslation/js/translator';

export default {
    name: 'link',

    order: 20,

    group: 'text-style',

    icon: '<span class="fa fa-link" aria-hidden="true"></span>',

    attributes: {
        title: __('oro.cms.wysiwyg.component.link.label')
    },

    result(rte, action) {
        const {editor} = this;
        const selection = rte.selection();

        if (action.isSelectionALink(selection)) {
            const selectedComponent = editor.getSelected();

            if (selectedComponent.get('type') === 'link') {
                const el = selectedComponent.view.el;

                if (el.parentNode) {
                    const textNode = document.createTextNode(selection.toString());

                    el.parentNode.insertBefore(textNode, el);
                }

                selectedComponent.destroy();
            } else {
                const linkElement = action.getLinkElement(selection);

                linkElement.classList.remove('link');
                linkElement.setAttribute('href', '#');
                rte.exec('unlink');
            }
        } else if (selection.toString() !== '') {
            rte.exec('createLink', '#');

            const linkElement = action.getLinkElement(rte.selection());

            linkElement.setAttribute('href', '');
            linkElement.classList.add('link');
        }
    },

    update(rte, action) {
        const selection = rte.selection();

        if (action.isSelectionALink(selection)) {
            action.btn.classList.add(rte.classes.active);
        }
    },

    isSelectionALink(selection) {
        if (!selection.anchorNode) {
            return false;
        }

        const parentNode = selection.anchorNode.parentNode;

        return parentNode && parentNode.nodeName === 'A' && parentNode.innerHTML === selection.toString();
    },

    getLinkElement(selection) {
        let linkElement = selection.anchorNode;

        if (linkElement.parentElement.tagName === 'A') {
            linkElement = linkElement.parentElement;
        } else {
            linkElement = linkElement.nextSibling;
        }

        return linkElement;
    }
};
