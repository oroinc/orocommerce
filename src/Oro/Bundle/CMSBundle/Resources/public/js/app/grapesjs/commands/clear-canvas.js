export default {
    run(editor) {
        editor.getSelectedAll().forEach(selected => editor.selectRemove(selected));
        editor.DomComponents.clear();
        editor.CssComposer.clear();
    }
};
