import __ from 'orotranslation/js/translator';
import ListMixin from './utils/list-mixins';

const listMixin = new ListMixin('UL', 'OL');

export default {
    name: 'insertUnorderedList',

    order: 30,

    group: 'list-style',

    icon: '<span class="fa fa-list-ul" aria-hidden="true"></span>',

    attributes: {
        title: __('oro.cms.wysiwyg.simple_actions.insert_unordered_list.title')
    },

    handlers: {
        'unordered:sublist:add': function(rte) {
            listMixin.processSubList(rte, this.editor, false);
        },
        'unordered:sublist:remove': function(rte) {
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
