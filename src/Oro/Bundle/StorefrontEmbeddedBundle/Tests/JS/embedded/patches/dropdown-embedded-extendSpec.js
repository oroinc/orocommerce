import 'jasmine-jquery';
import Dropdown from 'oroui/js/extend/bootstrap/bootstrap-dropdown';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import {installDropdownEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/dropdown-embedded-extend';
import html from 'text-loader!../Fixture/two-fragments.html';

describe('orostorefrontembedded/js/embedded/patches/dropdown-embedded-extend', () => {
    const proto = Dropdown.prototype;

    let originalDispose;
    let originalGetConfig;
    let dispose;
    let fragment;

    beforeEach(() => {
        window.setFixtures(html);

        originalDispose = proto.dispose;
        originalGetConfig = proto._getConfig;
        dispose = jasmine.createSpy('dispose');
        proto.dispose = dispose;
        proto._getConfig = () => ({menuContainer: document.body});
        installDropdownEmbeddedExtend();

        fragment = {
            containerEl: document.querySelector('.first-fragment-container'),
            overlayRoot: document.querySelector('.first-fragment-overlay')
        };
        setActiveFragment(fragment);
    });

    afterEach(() => {
        proto.dispose = originalDispose;
        proto._getConfig = originalGetConfig;
        clearActiveFragment(fragment);
    });

    it('returns the fragment overlay as the menu container', () => {
        const config = proto._getConfig.call({_element: document.querySelector('.first-fragment-element')});

        expect(config.menuContainer).toBe(fragment.overlayRoot);
    });

    it('returns the configured menu container unchanged', () => {
        const configuredContainer = document.querySelector('.second-fragment-container');
        proto._getConfig = () => ({menuContainer: configuredContainer});
        installDropdownEmbeddedExtend();

        const config = proto._getConfig.call({_element: document.querySelector('.first-fragment-element')});

        expect(config.menuContainer).toBe(configuredContainer);
    });

    it('disposes a dropdown that still has an element', () => {
        proto.dispose.call({_element: document.querySelector('.first-fragment-element')});

        expect(dispose).toHaveBeenCalled();
    });

    it('does not dispose a dropdown that has no element', () => {
        expect(() => proto.dispose.call({_element: null})).not.toThrow();
        expect(dispose).not.toHaveBeenCalled();
    });
});
