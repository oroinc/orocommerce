import {wrap} from 'underscore';
import FullscreenPopupView from 'orofrontend/default/js/app/views/fullscreen-popup-view';
import {overlayRootInsteadOfBody} from '../active-fragment';

export function installFullscreenPopupEmbeddedExtend() {
    const proto = FullscreenPopupView.prototype;

    proto.appendToContainer = wrap(proto.appendToContainer, function(appendToContainer, ...args) {
        return overlayRootInsteadOfBody(appendToContainer.apply(this, args), this.el);
    });
}
