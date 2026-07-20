import __ from 'orotranslation/js/translator';

const BASE_TRAITS = [
    {
        name: 'linkStyle',
        type: 'radio-select',
        label: __('oro.cms.wysiwyg.component.link.variant'),
        changeProp: true,
        options: []
    },
    {
        name: 'href',
        type: 'href',
        label: __('oro.cms.wysiwyg.component.link.href')
    },
    {
        name: 'title',
        type: 'text',
        label: __('oro.cms.wysiwyg.component.link.title')
    },
    {
        name: 'target',
        type: 'radio-select',
        label: __('oro.cms.wysiwyg.component.link.target'),
        options: [
            {id: '_self', label: '_self'},
            {id: '_blank', label: '_blank'}
        ]
    },
    {
        name: 'rel',
        type: 'text',
        label: __('oro.cms.wysiwyg.component.link.rel'),
        placeholder: 'nofollow noopener'
    }
];

export default BASE_TRAITS;
