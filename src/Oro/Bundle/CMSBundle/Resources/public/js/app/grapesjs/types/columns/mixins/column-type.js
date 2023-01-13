export default {
    editorEvents: {
        'component:selected': 'onSelected',
        'component:deselected': 'onDeselected',
        'styleable:change': 'onStyleUpdate'
    },

    getCodeModeState() {
        const state = this.editor.getState();

        return state.get('codeMode') || false;
    },

    onSelected(model) {
        if (this.getCodeModeState()) {
            model.set('resizable', false);
            return;
        }

        if (!model.is(this.componentType)) {
            return;
        }

        if (model.defaults.resizable && !model.get('resizable')) {
            model.set('resizable', model.defaults.resizable);
        }

        model.styleManager.enableStyleSectors();
    },

    onDeselected(model) {
        if (!model.is(this.componentType) || this.getCodeModeState()) {
            return;
        }

        model.styleManager.disableStyleSectors();
    },

    onStyleUpdate(selector, prop, {__clear = false}) {
        const selected = this.editor.getSelected();

        if (selected?.is(this.componentType)) {
            if (prop === '--grid-column-span') {
                selected.updateClasses({__clear, namespace: 'grid-col'});
            } else if (prop === '--grid-column-count') {
                selected.updateClasses({
                    __clear,
                    namespace: 'grid-columns',
                    devices: false,
                    replace: false
                });
            }
        }
    }
};
