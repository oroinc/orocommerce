import {wrap} from 'underscore';
import DialogWidget from 'orowindows/js/widget/dialog-widget';
import {getActiveFragment} from '../active-fragment';

export function installDialogEmbeddedExtend() {
    const proto = DialogWidget.prototype;

    proto.initialize = wrap(proto.initialize, function(initialize, options = {}) {
        const overlayRoot = getActiveFragment()?.overlayRoot;

        if (overlayRoot) {
            options.incrementalPosition = false;
            options.dialogOptions = {
                appendTo: overlayRoot,
                ...options.dialogOptions ?? this.options.dialogOptions
            };
        }

        return initialize.call(this, options);
    });
}
