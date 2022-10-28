export default {
    editorEvents: {
        'component:selected': 'onSelected',
        'component:deselected': 'onDeselected',
        'styleable:change': 'onStyleUpdate'
    },

    onSelected(model) {
        if (!model.is(this.componentType)) {
            return;
        }

        model.styleManager.enableStyleSectors();
    },

    onDeselected(model) {
        if (!model.is(this.componentType)) {
            return;
        }

        model.styleManager.disableStyleSectors();
    },

    onStyleUpdate(selector, prop, {__clear = false}) {
        const selected = this.editor.getSelected();

        if (selected?.is(this.componentType) && prop === '--grid-column-span') {
            selected.updateClasses({__clear});
        }
    }
};
