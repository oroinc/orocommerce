import 'jasmine-jquery';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import html from 'text-loader!../Fixture/two-fragments.html';
import viewportManager from 'oroui/js/viewport-manager';
import {installViewportEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/viewport-embedded-extend';

describe('orostorefrontembedded/js/embedded/patches/viewport-embedded-extend', () => {
    let originalGetBreakpoints;
    let contexts;

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
        originalGetBreakpoints = viewportManager.getBreakpoints;
        contexts = [];
        viewportManager.getBreakpoints = context => {
            contexts.push(context);
        };
        installViewportEmbeddedExtend();
    });

    afterEach(() => {
        viewportManager.getBreakpoints = originalGetBreakpoints;
    });

    it('reads the breakpoints of the active fragment container', () => {
        viewportManager.getBreakpoints();

        expect(contexts[0]).toBe(secondFragment.containerEl);
    });

    it('reads the breakpoints of the given context', () => {
        viewportManager.getBreakpoints(firstFragmentElement);

        expect(contexts[0]).toBe(firstFragmentElement);
    });

    it('reads the breakpoints of a fragment container in the document when no fragment is set', () => {
        [firstFragment, secondFragment].forEach(clearActiveFragment);

        viewportManager.getBreakpoints();

        expect(contexts[0]).toBe(firstFragment.containerEl);
    });

    it('does not hold on to a fragment container that left the document', () => {
        [firstFragment, secondFragment].forEach(clearActiveFragment);
        firstFragment.containerEl.remove();

        viewportManager.getBreakpoints();

        expect(contexts[0]).toBe(secondFragment.containerEl);
    });
});
