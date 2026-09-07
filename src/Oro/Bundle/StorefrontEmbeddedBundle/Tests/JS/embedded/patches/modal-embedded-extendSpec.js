import $ from 'jquery';
import 'jasmine-jquery';
import 'oroui/js/extend/bootstrap/bootstrap-modal';
import {BACKDROP_Z_INDEX, MODAL_Z_INDEX} from 'orostorefrontembedded/js/embedded/config';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import {installModalEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/modal-embedded-extend';
import html from 'text-loader!../Fixture/two-fragments.html';

describe('orostorefrontembedded/js/embedded/patches/modal-embedded-extend', () => {
    const proto = $.fn.modal.Constructor.prototype;

    let originalShowElement;
    let originalShowBackdrop;
    let showElement;
    let showBackdrop;
    let fragment;
    let modalElement;

    beforeEach(() => {
        window.setFixtures(html);

        originalShowElement = proto._showElement;
        originalShowBackdrop = proto._showBackdrop;
        showElement = jasmine.createSpy('_showElement');
        showBackdrop = jasmine.createSpy('_showBackdrop');
        proto._showElement = showElement;
        proto._showBackdrop = showBackdrop;
        installModalEmbeddedExtend();

        fragment = {
            containerEl: document.querySelector('.first-fragment-container'),
            overlayRoot: document.querySelector('.first-fragment-overlay')
        };
        setActiveFragment(fragment);

        modalElement = document.querySelector('.first-fragment-modal');
    });

    afterEach(() => {
        proto._showElement = originalShowElement;
        proto._showBackdrop = originalShowBackdrop;
        clearActiveFragment(fragment);
    });

    it('appends the modal to the overlay of its fragment', () => {
        proto._showElement.call({_element: modalElement});

        expect(modalElement.parentElement).toBe(fragment.overlayRoot);
        expect(modalElement.style.zIndex).toBe(String(MODAL_Z_INDEX));
        expect(showElement).toHaveBeenCalled();
    });

    it('does not move a modal that is already in the overlay', () => {
        fragment.overlayRoot.appendChild(modalElement);
        modalElement.style.zIndex = '';

        proto._showElement.call({_element: modalElement});

        expect(modalElement.style.zIndex).toBe('');
    });

    it('does not move a modal outside a fragment', () => {
        clearActiveFragment(fragment);
        const onHost = document.querySelector('.host-element');
        const originalParent = onHost.parentElement;

        proto._showElement.call({_element: onHost});

        expect(onHost.parentElement).toBe(originalParent);
    });

    it('appends the backdrop to the overlay of the fragment', () => {
        const backdrop = document.querySelector('.modal-backdrop-element');
        document.body.appendChild(backdrop);

        proto._showBackdrop.call({_element: modalElement, _backdrop: backdrop});

        expect(backdrop.parentElement).toBe(fragment.overlayRoot);
        expect(backdrop.style.zIndex).toBe(String(BACKDROP_Z_INDEX));
        expect(showBackdrop).toHaveBeenCalled();
    });

    it('does not move a backdrop outside the body', () => {
        const backdrop = document.querySelector('.modal-backdrop-element');
        const configuredContainer = document.querySelector('.second-fragment-container');
        configuredContainer.appendChild(backdrop);

        proto._showBackdrop.call({_element: modalElement, _backdrop: backdrop});

        expect(backdrop.parentElement).toBe(configuredContainer);
    });

    it('shows a modal without a backdrop', () => {
        expect(() => proto._showBackdrop.call({_element: modalElement, _backdrop: null})).not.toThrow();
        expect(showBackdrop).toHaveBeenCalled();
    });
});
