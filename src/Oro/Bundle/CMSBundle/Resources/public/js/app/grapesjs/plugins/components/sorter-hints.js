import GrapesJS from 'grapesjs';

export default GrapesJS.plugins.add('sorter-hints', editor => {
    const PERMITTED_PROP = 'dragPermitted';

    const onDragBlockStart = () => {
        editor.getModel().set(PERMITTED_PROP, true);
    };

    const onDragBlockEnd = () => {
        editor.getModel().set(PERMITTED_PROP, false);
    };

    const filterDragData = (DataTransfer, result) => {
        if (!editor.getModel().get(PERMITTED_PROP)) {
            result.content = false;
        }
    };

    editor.on('canvas:dragdata', filterDragData);
    editor.on('block:drag:start component:drag:start', onDragBlockStart);
    editor.on('block:drag:stop component:drag:end', onDragBlockEnd);

    const CustomSorter = editor.Utils.Sorter.extend({
        findPosition(dims, posX, posY) {
            const res = CustomSorter.__super__.findPosition.call(this, dims, posX, posY);

            if (this.targetModel?.is('columns')) {
                const foundIndex = dims.findIndex(dim => {
                    const {top, left, width, height} = dim;
                    if ((posX > left && posX < (left + width)) &&
                        (posY > top && (posY < (top + height)))) {
                        return true;
                    }
                    return false;
                });

                if (foundIndex !== -1) {
                    const found = dims[foundIndex];
                    const xCenter = found.left + found.width / 2;
                    return {
                        index: foundIndex,
                        indexEl: found.indexEl,
                        method: posX < xCenter ? 'before' : 'after'
                    };
                }
            }

            return res;
        },

        movePlaceholder(plh, dims, pos, trgDim) {
            if (this.targetModel?.is('columns')) {
                dims = dims.map(dim => {
                    dim.dir = false;
                    return dim;
                });
            }
            CustomSorter.__super__.movePlaceholder.call(this, plh, dims, pos, trgDim);
        },

        selectTargetModel(model, source) {
            if (!editor.getModel().get(PERMITTED_PROP)) {
                return;
            }

            CustomSorter.__super__.selectTargetModel.call(this, model, source);
        },

        onMove(event) {
            CustomSorter.__super__.onMove.call(this, event);
            if (!editor.getModel().get(PERMITTED_PROP)) {
                this.plh.style.display = 'none';
            }
        }
    });

    editor.Utils.Sorter = CustomSorter;
});
