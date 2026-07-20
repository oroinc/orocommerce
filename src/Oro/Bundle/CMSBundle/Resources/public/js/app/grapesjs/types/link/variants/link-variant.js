import __ from 'orotranslation/js/translator';
import {syncTextTrait} from './variant-utils';

/**
 * Enable container mode: cache text, restore cached nested content or clear textnode.
 */
function enableContainerMode(model) {
    model.set('droppable', true);

    if (model.cachedNestedHTML) {
        model.components(model.cachedNestedHTML);
        model.cachedNestedHTML = null;
    } else {
        const textNode = model.get('components').find(c => c.get('type') === 'textnode');

        if (textNode) {
            model.cachedLinkText = textNode.get('content');
            textNode.remove();
        }
    }

    model.removeTrait('text');
    model.stopListening(model, 'change:attributes:text', model.onLinkTextChange);
}

/**
 * Disable container mode: cache nested content as HTML, restore text.
 */
function disableContainerMode(model) {
    model.set('droppable', false);

    const el = model.getEl();

    if (el && el.children.length) {
        model.cachedNestedHTML = el.innerHTML;
    }

    const cachedText = model.cachedLinkText || 'Link';

    model.components([{
        type: 'textnode',
        content: cachedText
    }]);

    if (!model.getTrait('text')) {
        const dividerIndex = model.getTraits().findIndex(t => t.get('name') === 'divider');

        model.addTrait(
            {name: 'text', type: 'text', label: __('oro.cms.wysiwyg.component.link.text')},
            dividerIndex >= 0 ? {at: dividerIndex} : undefined
        );
    }

    syncTextTrait(model);
    model.listenTo(model, 'change:attributes:text', model.onLinkTextChange);
}

/**
 * Handle containerMode toggle.
 */
function onContainerModeChange(model, value) {
    if (value) {
        enableContainerMode(model);
    } else {
        disableContainerMode(model);
    }
}

/**
 * Detect if the model is already in container mode based on its children.
 * A link is in container mode if it has children that are not plain textnodes.
 */
function detectContainerMode(model) {
    const children = model.get('components');

    if (!children || !children.length) {
        return false;
    }

    return children.some(child => child.get('type') !== 'textnode');
}

export default {
    id: 'link',
    label: __('oro.cms.wysiwyg.component.link.style_link'),
    order: 10,

    detect(el) {
        return !el.classList.contains('btn');
    },

    classes: ['link'],
    droppable: false,

    defaultComponents: [{
        type: 'textnode',
        content: __('oro.cms.wysiwyg.component.link.content')
    }],

    traits: [
        {name: 'text', type: 'text', label: __('oro.cms.wysiwyg.component.link.text')},
        {name: 'divider', type: 'divider'},
        {
            name: 'containerMode',
            type: 'checkbox',
            label: __('oro.cms.wysiwyg.component.link.container_mode'),
            changeProp: true
        }
    ],

    onActivate(model) {
        const isContainer = detectContainerMode(model);

        model.set('containerMode', isContainer, {silent: true});

        if (isContainer) {
            model.set('droppable', true);
            model.removeTrait('text');
        } else {
            model.listenTo(model, 'change:attributes:text', model.onLinkTextChange);
            syncTextTrait(model);
        }

        model.listenTo(model, 'change:containerMode', onContainerModeChange);
    },

    onDeactivate(model) {
        model.stopListening(model, 'change:attributes:text', model.onLinkTextChange);
        model.stopListening(model, 'change:containerMode', onContainerModeChange);
    }
};
