import __ from 'orotranslation/js/translator';

export default [
    {
        name: 'bold',
        command: 'bold',
        order: 20,
        group: 'text-style',
        icon: '<span class="fa fa-bold" aria-hidden="true"></span>',
        attributes: {
            title: __('oro.cms.wysiwyg.simple_actions.bold.title')
        }
    }, {
        name: 'italic',
        command: 'italic',
        order: 20,
        group: 'text-style',
        icon: '<span class="fa fa-italic" aria-hidden="true"></span>',
        attributes: {
            title: __('oro.cms.wysiwyg.simple_actions.italic.title')
        }
    }, {
        name: 'underline',
        command: 'underline',
        order: 20,
        group: 'text-style',
        icon: '<span class="fa fa-underline" aria-hidden="true"></span>',
        attributes: {
            title: __('oro.cms.wysiwyg.simple_actions.underline.title')
        }
    }, {
        name: 'strikethrough',
        command: 'strikethrough',
        order: 20,
        group: 'text-style',
        icon: '<span class="fa fa-strikethrough" aria-hidden="true"></span>',
        attributes: {
            title: __('oro.cms.wysiwyg.simple_actions.strikethrough.title')
        }
    }, {
        name: 'subscript',
        command: 'subscript',
        order: 40,
        group: 'text-level',
        icon: '<span class="fa fa-subscript" aria-hidden="true"></span>',
        attributes: {
            title: __('oro.cms.wysiwyg.simple_actions.subscript.title')
        }
    }, {
        name: 'superscript',
        command: 'superscript',
        order: 40,
        group: 'text-level',
        icon: '<span class="fa fa-superscript" aria-hidden="true"></span>',
        attributes: {
            title: __('oro.cms.wysiwyg.simple_actions.superscript.title')
        }
    }
];
