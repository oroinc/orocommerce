import __ from 'orotranslation/js/translator';
import ListMixin from './utils/list-mixins';

const listMixin = new ListMixin('OL', 'UL');

export default {
    name: 'insertOrderedList',

    order: 30,

    group: 'list-style',

    icon: '<span class="fa fa-list-ol" aria-hidden="true"></span>',

    attributes: {
        title: __('oro.cms.wysiwyg.simple_actions.insert_ordered_list.title')
    },

    handlers: {
        'ordered:sublist:add': function(rte) {
            listMixin.processSubList(rte, this.editor, false);
        },
        'ordered:sublist:remove': function(rte) {
            listMixin.processSubList(rte, this.editor, true);
        }
    },

    result(rte) {
        listMixin.processList(rte, this.editor);
    },

    onKeyDown(rte, event) {
        if (listMixin.isList(rte)) {
            listMixin.dispatchKeyDownEvent(rte, this.editor, event);
        }
    }
};
