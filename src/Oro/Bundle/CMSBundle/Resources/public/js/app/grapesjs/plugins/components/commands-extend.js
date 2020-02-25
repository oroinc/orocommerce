export default class CommandsExtend {
    constructor(editor) {
        this.editor = editor;

        this.extendComponentSelect();
    }

    extendComponentSelect() {
        this.editor.Commands.extend('core:component-select', {
            updateToolbarPos(el, elPos) {
                const {canvas} = this;
                const unit = 'px';
                const toolbarEl = canvas.getToolbarEl();
                const toolbarStyle = toolbarEl.style;
                const iframe = canvas.getFrameEl();

                toolbarStyle.opacity = 0;
                const pos = canvas.getTargetToElementDim(toolbarEl, el, {
                    elPos,
                    event: 'toolbarPosUpdate'
                });

                if (pos) {
                    if (pos.canvasTop >= pos.elementTop && pos.canvasTop <= (pos.elementTop + pos.elementHeight)) {
                        pos.top = pos.canvasTop;
                    }

                    // Check left position of the toolbar
                    const elRight = pos.elementLeft + pos.elementWidth;
                    let left = elRight - pos.targetWidth;

                    if (elRight > (pos.canvasLeft + iframe.clientWidth)) {
                        left = pos.canvasLeft + iframe.clientWidth - pos.targetWidth;
                    }

                    left = left < pos.canvasLeft ? pos.canvasLeft : left;
                    toolbarStyle.top = `${pos.top}${unit}`;
                    toolbarStyle.left = `${left}${unit}`;
                    toolbarStyle.opacity = '';
                }
            }
        });
    }

    destroy() {
        delete this.editor;
    }
}
