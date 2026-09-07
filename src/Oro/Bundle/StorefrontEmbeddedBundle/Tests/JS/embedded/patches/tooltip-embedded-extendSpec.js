import $ from 'jquery';
import 'jasmine-jquery';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import html from 'text-loader!../Fixture/two-fragments.html';
import 'oroui/js/extend/bootstrap/bootstrap-tooltip';
import {installTooltipEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/tooltip-embedded-extend';

describe('orostorefrontembedded/js/embedded/patches/tooltip-embedded-extend', () => {
    const proto = $.fn.tooltip.Constructor.prototype;

    let originalGetContainer;

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
        originalGetContainer = proto._getContainer;
        proto._getContainer = () => document.body;
        installTooltipEmbeddedExtend();
    });

    afterEach(() => {
        proto._getContainer = originalGetContainer;
    });

    it('returns the overlay of the fragment holding the element', () => {
        expect(proto._getContainer.call({element: firstFragmentElement})).toBe(firstFragment.overlayRoot);
    });

    it('returns the body for an element outside a fragment', () => {
        [firstFragment, secondFragment].forEach(clearActiveFragment);

        expect(proto._getContainer.call({element: document.querySelector('.host-element')})).toBe(document.body);
    });
});
