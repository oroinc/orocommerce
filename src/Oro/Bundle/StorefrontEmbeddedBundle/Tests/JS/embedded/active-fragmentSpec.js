import {
    clearActiveFragment,
    findOverlayRoot,
    getActiveFragment,
    isBodyContainer,
    overlayRootInsteadOfBody,
    setActiveFragment
} from 'orostorefrontembedded/js/embedded/active-fragment';
import html from 'text-loader!./Fixture/two-fragments.html';

describe('orostorefrontembedded/js/embedded/active-fragment', () => {
    let firstFragment;
    let secondFragment;

    const createFragment = name => ({
        containerEl: document.querySelector(`.${name}-fragment-container`),
        overlayRoot: document.querySelector(`.${name}-fragment-overlay`)
    });

    beforeEach(() => {
        window.setFixtures(html);

        firstFragment = createFragment('first');
        secondFragment = createFragment('second');
    });

    afterEach(() => {
        clearActiveFragment(firstFragment);
        clearActiveFragment(secondFragment);
    });

    it('returns no active fragment when none is set', () => {
        expect(getActiveFragment()).toBeNull();
    });

    it('returns the fragment set last as the active one', () => {
        setActiveFragment(firstFragment);
        setActiveFragment(secondFragment);

        expect(getActiveFragment()).toBe(secondFragment);
    });

    it('returns a fragment set again as the active one', () => {
        setActiveFragment(firstFragment);
        setActiveFragment(secondFragment);
        setActiveFragment(firstFragment);

        expect(getActiveFragment()).toBe(firstFragment);
    });

    it('returns the previous fragment after the active one is cleared', () => {
        setActiveFragment(firstFragment);
        setActiveFragment(secondFragment);

        clearActiveFragment(secondFragment);

        expect(getActiveFragment()).toBe(firstFragment);
    });

    it('keeps the active fragment when an unknown one is cleared', () => {
        setActiveFragment(firstFragment);

        clearActiveFragment(createFragment('unknown'));

        expect(getActiveFragment()).toBe(firstFragment);
    });

    describe('findOverlayRoot', () => {
        it('returns the overlay of the fragment containing the node', () => {
            const firstFragmentElement = document.querySelector('.first-fragment-element');
            setActiveFragment(firstFragment);
            setActiveFragment(secondFragment);

            expect(findOverlayRoot(firstFragmentElement)).toBe(firstFragment.overlayRoot);
        });

        it('returns the overlay for a node inside that overlay', () => {
            const overlayElement = document.querySelector('.first-fragment-element');
            firstFragment.overlayRoot.appendChild(overlayElement);
            setActiveFragment(firstFragment);
            setActiveFragment(secondFragment);

            expect(findOverlayRoot(overlayElement)).toBe(firstFragment.overlayRoot);
        });

        it('returns the overlay of the active fragment for a node outside every fragment', () => {
            setActiveFragment(firstFragment);
            setActiveFragment(secondFragment);

            expect(findOverlayRoot(document.querySelector('.host-element'))).toBe(secondFragment.overlayRoot);
            expect(findOverlayRoot(null)).toBe(secondFragment.overlayRoot);
        });

        it('returns null when no fragment is set', () => {
            expect(findOverlayRoot(document.querySelector('.host-element'))).toBeNull();
        });
    });

    describe('overlayRootInsteadOfBody', () => {
        it('returns the fragment overlay instead of the body', () => {
            setActiveFragment(firstFragment);

            expect(overlayRootInsteadOfBody(document.body, null)).toBe(firstFragment.overlayRoot);
        });

        it('returns the given container unchanged', () => {
            const chosen = document.querySelector('.second-fragment-container');
            setActiveFragment(firstFragment);

            expect(overlayRootInsteadOfBody(chosen, null)).toBe(chosen);
        });

        it('returns the body when no fragment is set', () => {
            expect(overlayRootInsteadOfBody(document.body, null)).toBe(document.body);
        });
    });

    describe('isBodyContainer', () => {
        it('returns true for the body, given as an element or a selector', () => {
            expect(isBodyContainer(document.body)).toBe(true);
            expect(isBodyContainer('body')).toBe(true);
        });

        it('returns false for any other element', () => {
            expect(isBodyContainer(document.querySelector('.host-element'))).toBe(false);
        });
    });
});
