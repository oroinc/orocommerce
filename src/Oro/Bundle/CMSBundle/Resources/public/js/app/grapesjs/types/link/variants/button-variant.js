import __ from 'orotranslation/js/translator';
import {syncTextTrait, hasSimpleTextContent} from './variant-utils';

/**
 * Handle icon enabled toggle (button style).
 */
function onIconEnabledToggle(model, value) {
    if (value) {
        if (!model.linkIcon) {
            model.linkIcon = model.append({
                type: 'icon',
                tagName: 'svg',
                iconId: 'add-note',
                draggable: false,
                copyable: false,
                removable: false
            })[0];
        }

        if (model.get('iconBefore')) {
            model.append(model.linkIcon, {at: 0});
        }

        model.addTrait({
            id: 'iconBefore',
            name: 'iconBefore',
            type: 'checkbox',
            label: __('oro.cms.wysiwyg.component.link_button.icon_before'),
            changeProp: true
        });
    } else {
        if (model.linkIcon) {
            model.linkIcon.remove();
            model.linkIcon = null;
        }

        model.set('iconBefore', false, {silent: true});
        model.removeTrait('iconBefore');
    }
}

/**
 * Handle button style class change.
 */
function onButtonStyleChange(model, newStyle) {
    const styleClasses = ['btn--outlined', 'btn--plain', 'btn--link'];

    styleClasses.forEach(cls => model.removeClass(cls));

    if (newStyle) {
        model.addClass(newStyle);
    }
}

/**
 * Handle icon position change (before/after text).
 */
function onIconBeforeChange(model, value) {
    const [icon] = model.findType('icon');

    if (icon) {
        value ? model.append(icon, {at: 0}) : model.append(icon);
    }
}

export default {
    id: 'button',
    label: __('oro.cms.wysiwyg.component.link.style_button'),
    order: 30,

    detect(el) {
        return el.classList.contains('btn');
    },

    classes: ['btn'],
    droppable: false,

    defaultComponents: [{
        type: 'textnode',
        content: __('oro.cms.wysiwyg.component.link_button.content')
    }],

    traits: [
        {name: 'text', type: 'text', label: __('oro.cms.wysiwyg.component.link.text')},
        {name: 'divider', type: 'divider'},
        {
            name: 'buttonStyle',
            type: 'radio-select',
            label: __('oro.cms.wysiwyg.component.link_button.style'),
            changeProp: true,
            options: [
                {id: '', label: __('oro.cms.wysiwyg.component.link_button.style_primary')},
                {id: 'btn--outlined', label: __('oro.cms.wysiwyg.component.link_button.style_outlined')},
                {id: 'btn--plain', label: __('oro.cms.wysiwyg.component.link_button.style_plain')}
            ]
        },
        {
            name: 'iconEnabled',
            type: 'checkbox',
            label: __('oro.cms.wysiwyg.component.link_button.icon_enabled'),
            changeProp: true
        }
    ],

    onActivate(model) {
        model.listenTo(model, 'change:buttonStyle', onButtonStyleChange);
        model.listenTo(model, 'change:iconBefore', onIconBeforeChange);

        const [icon] = model.findType('icon');

        model.linkIcon = icon || null;

        if (model.linkIcon) {
            const firstChild = model.getChildAt(0);

            model.set('iconBefore', !!firstChild && firstChild.is('icon'), {silent: true});
        }

        const iconEnabled = !!model.linkIcon || !!model.get('iconEnabled');

        onIconEnabledToggle(model, iconEnabled);

        if (model.linkIcon) {
            model.linkIcon.set({
                draggable: false,
                copyable: false,
                removable: false
            });
            model.linkIcon.initToolbar({reset: true});
        }

        model.set('iconEnabled', iconEnabled);
        model.listenTo(model, 'change:iconEnabled', onIconEnabledToggle);

        if (hasSimpleTextContent(model)) {
            model.listenTo(model, 'change:attributes:text', model.onLinkTextChange);
            syncTextTrait(model);
        } else {
            model.removeTrait('text');
        }
    },

    onDeactivate(model) {
        model.stopListening(model, 'change:attributes:text', model.onLinkTextChange);
        model.stopListening(model, 'change:iconEnabled', onIconEnabledToggle);
        model.stopListening(model, 'change:buttonStyle', onButtonStyleChange);
        model.stopListening(model, 'change:iconBefore', onIconBeforeChange);

        if (model.linkIcon) {
            model.linkIcon.remove();
            model.linkIcon = null;
        }

        model.removeTrait('iconBefore');
    }
};
