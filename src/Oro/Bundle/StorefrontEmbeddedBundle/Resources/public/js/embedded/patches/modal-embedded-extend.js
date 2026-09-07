import $ from 'jquery';
import {wrap} from 'underscore';
import {MODAL_Z_INDEX, BACKDROP_Z_INDEX} from '../config';
import {findOverlayRoot, isBodyContainer} from '../active-fragment';

function placeIn(overlayRoot, el, zIndex) {
    if (!overlayRoot || el.parentElement === overlayRoot) {
        return;
    }

    overlayRoot.appendChild(el);
    el.style.zIndex = zIndex;
}

export function installModalEmbeddedExtend() {
    const Modal = $.fn.modal && $.fn.modal.Constructor;
    const proto = Modal && Modal.prototype;
    if (!proto || typeof proto._showElement !== 'function' || typeof proto._showBackdrop !== 'function') {
        console.warn(
            '[oro-embedded-fragment] bootstrap modal prototype not patchable; ' +
            'modals will render outside the overlay root'
        );
        return;
    }

    proto._showElement = wrap(proto._showElement, function(showElement, ...args) {
        placeIn(findOverlayRoot(this._element), this._element, MODAL_Z_INDEX);

        return showElement.apply(this, args);
    });

    proto._showBackdrop = wrap(proto._showBackdrop, function(showBackdrop, ...args) {
        const result = showBackdrop.apply(this, args);

        if (this._backdrop && isBodyContainer(this._backdrop.parentElement)) {
            placeIn(findOverlayRoot(this._element), this._backdrop, BACKDROP_Z_INDEX);
        }

        return result;
    });
}
