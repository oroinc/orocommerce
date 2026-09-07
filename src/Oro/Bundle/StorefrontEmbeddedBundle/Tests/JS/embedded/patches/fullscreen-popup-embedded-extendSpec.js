import 'jasmine-jquery';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import html from 'text-loader!../Fixture/two-fragments.html';
import FullscreenPopupView from 'orofrontend/default/js/app/views/fullscreen-popup-view';
import {
    installFullscreenPopupEmbeddedExtend
} from 'orostorefrontembedded/js/embedded/patches/fullscreen-popup-embedded-extend';

describe('orostorefrontembedded/js/embedded/patches/fullscreen-popup-embedded-extend', () => {
    const proto = FullscreenPopupView.prototype;

    let originalAppendToContainer;

    let firstFragment;
    let secondFragment;
    let firstFragmentElement;

    const createFragment = name => ({
        containerEl: document.querySelector(`.${name}-fragment-container`),
        overlayRoot: document.querySelector(`.${name}-fragment-overlay`)
    });

    beforeEach(() => {
        window.setFixtures(html);

        firstFragment = createFragment('first');
        secondFragment = createFragment('second');
        firstFragmentElement = document.querySelector('.first-fragment-element');

        setActiveFragment(firstFragment);
        setActiveFragment(secondFragment);
    });

    afterEach(() => {
        [firstFragment, secondFragment].forEach(clearActiveFragment);
    });

    beforeEach(() => {
        originalAppendToContainer = proto.appendToContainer;
        proto.appendToContainer = () => document.body;
        installFullscreenPopupEmbeddedExtend();
    });

    afterEach(() => {
        proto.appendToContainer = originalAppendToContainer;
    });

    it('returns the overlay of the fragment holding the element', () => {
        expect(proto.appendToContainer.call({el: firstFragmentElement})).toBe(firstFragment.overlayRoot);
    });

    it('returns the body for an element outside a fragment', () => {
        [firstFragment, secondFragment].forEach(clearActiveFragment);

        expect(proto.appendToContainer.call({el: document.querySelector('.host-element')})).toBe(document.body);
    });
});
