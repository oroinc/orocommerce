import {SCROLL_LOCK_CLASS} from './config';

const MODAL_IS_OPENED_CLASS = 'modal-is-opened';

const openOverlays = new Set();

export function lockHostScrollFor(cid) {
    openOverlays.add(cid);
    holdHostScroll(true);
}

export function unlockHostScrollFor(cid) {
    openOverlays.delete(cid);
    holdHostScroll(openOverlays.size > 0);
}

export function unlockHostScroll() {
    openOverlays.clear();
    holdHostScroll(false);
}

function holdHostScroll(held) {
    const alreadyHeld = document.body.classList.contains(SCROLL_LOCK_CLASS);

    if (held && !alreadyHeld) {
        document.body.style.paddingRight = `${visibleScrollbarWidth()}px`;
    }

    document.body.classList.toggle(SCROLL_LOCK_CLASS, held);
    document.body.classList.toggle(MODAL_IS_OPENED_CLASS, held);

    if (!held) {
        document.body.style.paddingRight = '';
    }
}

function visibleScrollbarWidth() {
    return window.innerWidth - document.documentElement.clientWidth;
}
