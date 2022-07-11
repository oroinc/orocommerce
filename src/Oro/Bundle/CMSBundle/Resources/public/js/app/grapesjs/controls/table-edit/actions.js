import __ from 'orotranslation/js/translator';

export default [
    {
        command: 'insert-row-before',
        group: 'row',
        label: __('oro.cms.wysiwyg.toolbar.table.row.before'),
        // eslint-disable-next-line
        icon: `<svg width="24" height="24"><path fill-rule="nonzero" d="M6 4a1 1 0 110 2H5v6h14V6h-1a1 1 0 010-2h2c.6 0 1 .4 1 1v13a2 2 0 01-2 2H5a2 2 0 01-2-2V5c0-.6.4-1 1-1h2zm5 10H5v4h6v-4zm8 0h-6v4h6v-4zM12 3c.5 0 1 .4 1 .9V6h2a1 1 0 010 2h-2v2a1 1 0 01-2 .1V8H9a1 1 0 010-2h2V4c0-.6.4-1 1-1z"/></svg>`
    },
    {
        command: 'insert-row-after',
        group: 'row',
        label: __('oro.cms.wysiwyg.toolbar.table.row.after'),
        context: ['cell', 'row'],
        // eslint-disable-next-line
        icon: `<svg width="24" height="24"><path fill-rule="nonzero" d="M12 13c.5 0 1 .4 1 .9V16h2a1 1 0 01.1 2H13v2a1 1 0 01-2 .1V18H9a1 1 0 01-.1-2H11v-2c0-.6.4-1 1-1zm6 7a1 1 0 010-2h1v-6H5v6h1a1 1 0 010 2H4a1 1 0 01-1-1V6c0-1.1.9-2 2-2h14a2 2 0 012 2v13c0 .5-.4 1-.9 1H18zM11 6H5v4h6V6zm8 0h-6v4h6V6z"/></svg>`
    },
    {
        command: 'delete-row',
        group: 'row',
        label: __('oro.cms.wysiwyg.toolbar.table.row.delete'),
        context: ['cell', 'row'],
        // eslint-disable-next-line
        icon: `<svg width="24" height="24"><path fill-rule="nonzero" d="M19 4a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6c0-1.1.9-2 2-2h14zm0 2H5v3h2.5v2H5v2h2.5v2H5v3h14v-3h-2.5v-2H19v-2h-2.5V9H19V6zm-4.7 1.8l1.2 1L13 12l2.6 3.3-1.2 1-2.3-3-2.3 3-1.2-1L11 12 8.5 8.7l1.2-1 2.3 3 2.3-3z"/></svg>`
    },
    {
        command: 'insert-column-before',
        group: 'column',
        label: __('oro.cms.wysiwyg.toolbar.table.column.before'),
        context: 'cell',
        // eslint-disable-next-line
        icon: `<svg width="24" height="24"><path fill-rule="nonzero" d="M19 4a2 2 0 012 2v12a2 2 0 01-2 2H4a1 1 0 01-1-1v-2a1 1 0 012 0v1h8V6H5v1a1 1 0 11-2 0V5c0-.6.4-1 1-1h15zm0 9h-4v5h4v-5zM8 8c.5 0 1 .4 1 .9V11h2a1 1 0 01.1 2H9v2a1 1 0 01-2 .1V13H5a1 1 0 01-.1-2H7V9c0-.6.4-1 1-1zm11-2h-4v5h4V6z"/></svg>`
    },
    {
        command: 'insert-column-after',
        group: 'column',
        label: __('oro.cms.wysiwyg.toolbar.table.column.after'),
        context: 'cell',
        // eslint-disable-next-line
        icon: `<svg width="24" height="24"><path fill-rule="nonzero" d="M20 4c.6 0 1 .4 1 1v2a1 1 0 01-2 0V6h-8v12h8v-1a1 1 0 012 0v2c0 .5-.4 1-.9 1H5a2 2 0 01-2-2V6c0-1.1.9-2 2-2h15zM9 13H5v5h4v-5zm7-5c.5 0 1 .4 1 .9V11h2a1 1 0 01.1 2H17v2a1 1 0 01-2 .1V13h-2a1 1 0 01-.1-2H15V9c0-.6.4-1 1-1zM9 6H5v5h4V6z"/></svg>`
    },
    {
        command: 'delete-column',
        group: 'column',
        label: __('oro.cms.wysiwyg.toolbar.table.column.delete'),
        context: 'cell',
        // eslint-disable-next-line
        icon: `<svg width="24" height="24"><path fill-rule="nonzero" d="M19 4a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6c0-1.1.9-2 2-2h14zm-4 4h-2V6h-2v2H9V6H5v12h4v-2h2v2h2v-2h2v2h4V6h-4v2zm.3.5l1 1.2-3 2.3 3 2.3-1 1.2L12 13l-3.3 2.6-1-1.2 3-2.3-3-2.3 1-1.2L12 11l3.3-2.5z"/></svg>`
    },
    {
        command: 'select-parent',
        group: 'table',
        label: __('oro.cms.wysiwyg.toolbar.selectParent'),
        context: 'cell',
        icon: `<span class="fa fa-arrow-up"></span>`
    },
    {
        command: 'delete-table',
        group: 'table',
        label: __('oro.cms.wysiwyg.toolbar.table.delete'),
        // eslint-disable-next-line
        icon: `<svg width="24" height="24"><g fill-rule="nonzero"><path d="M19 4a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6c0-1.1.9-2 2-2h14zM5 6v12h14V6H5z"/><path d="M14.4 8.6l1 1-2.3 2.4 2.3 2.4-1 1-2.4-2.3-2.4 2.3-1-1 2.3-2.4-2.3-2.4 1-1 2.4 2.3z"/></g></svg>`
    }
];
