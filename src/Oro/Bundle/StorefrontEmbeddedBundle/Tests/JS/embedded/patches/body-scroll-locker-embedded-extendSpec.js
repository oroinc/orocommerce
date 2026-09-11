import 'jasmine-jquery';
import bodyScrollLocker from 'oroui/js/app/services/body-scroll-locker';
import {SCROLL_LOCK_CLASS} from 'orostorefrontembedded/js/embedded/config';
import {unlockHostScroll} from 'orostorefrontembedded/js/embedded/host-scroll';
import {
    installBodyScrollLockerEmbeddedExtend
} from 'orostorefrontembedded/js/embedded/patches/body-scroll-locker-embedded-extend';

describe('orostorefrontembedded/js/embedded/patches/body-scroll-locker-embedded-extend', () => {
    let originalAddLocker;
    let originalRemoveLocker;

    const resetBody = () => {
        unlockHostScroll();
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.paddingRight = '';
    };

    beforeEach(() => {
        originalAddLocker = bodyScrollLocker.addLocker;
        originalRemoveLocker = bodyScrollLocker.removeLocker;
        resetBody();
        installBodyScrollLockerEmbeddedExtend();
    });

    afterEach(() => {
        bodyScrollLocker.addLocker = originalAddLocker;
        bodyScrollLocker.removeLocker = originalRemoveLocker;
        resetBody();
    });

    it('leaves the host body position and offset alone while an overlay is open', () => {
        bodyScrollLocker.addLocker('first-modal');

        expect(document.body.style.position).toBe('');
        expect(document.body.style.top).toBe('');
    });

    it('leaves the host scroll position alone when the overlay closes', () => {
        const scrollTo = spyOn(window, 'scrollTo');

        bodyScrollLocker.addLocker('first-modal');
        bodyScrollLocker.removeLocker('first-modal');

        expect(scrollTo).not.toHaveBeenCalled();
        expect(document.body.style.position).toBe('');
        expect(document.body.style.top).toBe('');
    });

    it('asks the host to hold its scroll while an overlay is open', () => {
        bodyScrollLocker.addLocker('first-modal');

        expect(document.body).toHaveClass(SCROLL_LOCK_CLASS);
    });

    it('marks the body while an overlay is open, for the select2 stacking rules', () => {
        bodyScrollLocker.addLocker('first-modal');

        expect(document.body).toHaveClass('modal-is-opened');
    });

    it('pads the host body by the width of the scrollbar it is about to hide', () => {
        bodyScrollLocker.addLocker('first-modal');

        expect(document.body.style.paddingRight)
            .toBe(`${window.innerWidth - document.documentElement.clientWidth}px`);
    });

    it('measures the scrollbar once, before the first overlay hides it', () => {
        bodyScrollLocker.addLocker('first-modal');
        document.body.style.paddingRight = '4242px';

        bodyScrollLocker.addLocker('second-modal');

        expect(document.body.style.paddingRight).toBe('4242px');
    });

    it('keeps the hold until the last overlay closes', () => {
        bodyScrollLocker.addLocker('first-modal');
        bodyScrollLocker.addLocker('second-modal');

        bodyScrollLocker.removeLocker('first-modal');

        expect(document.body).toHaveClass(SCROLL_LOCK_CLASS);

        bodyScrollLocker.removeLocker('second-modal');

        expect(document.body).not.toHaveClass(SCROLL_LOCK_CLASS);
        expect(document.body).not.toHaveClass('modal-is-opened');
    });

    it('releases the scrollbar padding together with the hold', () => {
        bodyScrollLocker.addLocker('first-modal');
        bodyScrollLocker.removeLocker('first-modal');

        expect(document.body.style.paddingRight).toBe('');
    });

    it('ignores an overlay that never opened', () => {
        bodyScrollLocker.addLocker('first-modal');

        bodyScrollLocker.removeLocker('overlay-that-never-opened');

        expect(document.body).toHaveClass(SCROLL_LOCK_CLASS);
    });

    it('lets the host scroll again when the fragment goes away with an overlay still open', () => {
        bodyScrollLocker.addLocker('first-modal');
        bodyScrollLocker.addLocker('second-modal');

        unlockHostScroll();

        expect(document.body).not.toHaveClass(SCROLL_LOCK_CLASS);
        expect(document.body).not.toHaveClass('modal-is-opened');
        expect(document.body.style.paddingRight).toBe('');
    });

    it('starts counting overlays afresh after the fragment goes away', () => {
        bodyScrollLocker.addLocker('modal-of-a-disposed-fragment');
        unlockHostScroll();

        bodyScrollLocker.addLocker('modal-of-the-next-fragment');
        bodyScrollLocker.removeLocker('modal-of-the-next-fragment');

        expect(document.body).not.toHaveClass(SCROLL_LOCK_CLASS);
    });
});
