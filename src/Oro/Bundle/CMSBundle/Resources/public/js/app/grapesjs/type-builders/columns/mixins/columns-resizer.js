import __ from 'orotranslation/js/translator';

export default {
    tl: 0,
    tc: 0,
    tr: 0,
    bl: 0,
    br: 0,
    bc: 0,
    minDim: 1,
    maxDim: 12,
    step: 1,
    currentUnit: 0,
    unitWidth: '',
    keyWidth: '--grid-column-span',
    onStart(event, {resizer}) {
        resizer.placeholder = document.createElement('div');
        resizer.placeholder.classList.add('column-resizer-placeholder');

        resizer.spanNumber = document.createElement('div');
        resizer.spanNumber.classList.add('span-number');

        resizer.placeholder.append(resizer.spanNumber);
        resizer.container.append(resizer.placeholder);
    },
    onEnd() {
        this.placeholder && this.placeholder.remove();

        this.selectedHandler.style.right = '';
        this.selectedHandler.style.left = '';

        if (this.toSpan) {
            this.targetModel.setSpan(this.toSpan);
        }
    },
    updateTarget(target, {w}, {resizer}) {
        const model = target.__cashData.model;
        const parent = target.parentNode;
        const parentModel = parent.__cashData.model;
        const columnWidth = parentModel.getColumnWidthWithGap();
        const span = model.getSpan();
        const {x: deltaX} = resizer.delta;
        const calcSpan = Math.round(deltaX / columnWidth);
        let toSpan;
        let willSpan;
        resizer.targetModel = model;

        resizer.placeholder.classList.toggle('cl', resizer.handlerAttr === 'cl');

        if (resizer.handlerAttr === 'cr') {
            if (deltaX > 0) {
                toSpan = calcSpan;
                willSpan = parseInt(span) + parseInt(toSpan);
                if (willSpan <= parseInt(parentModel.getColumnsCount())) {
                    resizer.placeholder.style.right = `${-(toSpan * columnWidth)}px`;
                    resizer.selectedHandler.style.right = `${-(toSpan * columnWidth)}px`;
                }
            } else {
                toSpan = calcSpan;
                willSpan = parseInt(span) + parseInt(toSpan);
                if (willSpan > 0) {
                    resizer.placeholder.style.right = `${-(toSpan * columnWidth)}px`;
                    resizer.selectedHandler.style.right = `${Math.abs(toSpan * columnWidth)}px`;
                }
            }
        }

        if (resizer.handlerAttr === 'cl') {
            if (deltaX < 0) {
                toSpan = -calcSpan;
                willSpan = parseInt(span) + parseInt(toSpan);
                if (willSpan <= parseInt(parentModel.getColumnsCount())) {
                    resizer.placeholder.style.left = `${-(toSpan * columnWidth)}px`;
                    resizer.selectedHandler.style.left = `${-(toSpan * columnWidth)}px`;
                }
            } else {
                toSpan = -calcSpan;
                willSpan = parseInt(span) + parseInt(toSpan);
                if (willSpan > 0) {
                    resizer.placeholder.style.left = `${Math.abs(toSpan * columnWidth)}px`;
                    resizer.selectedHandler.style.left = `${Math.abs(toSpan * columnWidth)}px`;
                }
            }
        }

        if (willSpan > parseInt(parentModel.getColumnsCount())) {
            willSpan = parseInt(parentModel.getColumnsCount());
        }

        if (willSpan > 0) {
            resizer.spanNumber.innerText = __('oro.cms.wysiwyg.component.columns.resizer_span', {
                spans: willSpan
            });
            resizer.toSpan = willSpan;
        }
    }
};
