import GrapesJS from 'grapesjs';

export default GrapesJS.plugins.add('sorter-hints', (editor, options) => {
    editor.Utils.Sorter = editor.Utils.Sorter.extend({
        findPosition(dims, posX, posY) {
            const res = this.constructor.__super__.findPosition.call(this, dims, posX, posY);

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
            this.constructor.__super__.movePlaceholder.call(this, plh, dims, pos, trgDim);
        }
    });
});
