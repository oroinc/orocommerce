import {wrap} from 'underscore';
import viewportManager from 'oroui/js/viewport-manager';
import {getActiveFragment} from '../active-fragment';
import {BLOCK_ATTRIBUTE} from '../config';

export function installViewportEmbeddedExtend() {
    viewportManager.getBreakpoints = wrap(
        viewportManager.getBreakpoints,
        function(getBreakpoints, context, callback) {
            return getBreakpoints.call(this, context ?? findFragmentContainer(), callback);
        }
    );
}

function findFragmentContainer() {
    return getActiveFragment()?.containerEl ?? document.querySelector(`[${BLOCK_ATTRIBUTE}]`);
}
