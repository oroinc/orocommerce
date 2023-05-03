import __ from 'orotranslation/js/translator';

export default {
    run(editor) {
        if (confirm(__('oro.cms.wysiwyg.commands.clear_canvas.confirm'))) {
            editor.getSelectedAll().forEach(selected => editor.selectRemove(selected));
            editor.DomComponents.clear();
            editor.CssComposer.clear();
        }
    }
};
