import 'jasmine-jquery';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import {installLoadingMaskEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/loading-mask-embedded-extend';
import html from 'text-loader!../Fixture/two-fragments.html';

describe('orostorefrontembedded/js/embedded/patches/loading-mask-embedded-extend', () => {
    const proto = LoadingMaskView.prototype;

    let originalAttach;
    let attachedContainer;
    let fragment;

    beforeEach(() => {
        window.setFixtures(html);

        originalAttach = proto.attach;
        attachedContainer = undefined;
        proto.attach = function() {
            attachedContainer = this.container;
        };
        installLoadingMaskEmbeddedExtend();

        fragment = {
            containerEl: document.querySelector('.first-fragment-container'),
            overlayRoot: document.querySelector('.first-fragment-overlay')
        };
        setActiveFragment(fragment);
    });

    afterEach(() => {
        proto.attach = originalAttach;
        clearActiveFragment(fragment);
    });

    it('attaches the mask to the fragment overlay instead of the body', () => {
        const mask = {container: document.body, el: document.querySelector('.first-fragment-element')};

        proto.attach.call(mask);

        expect(attachedContainer).toBe(fragment.overlayRoot);
    });

    it('attaches the mask to the configured container', () => {
        const configuredContainer = document.querySelector('.second-fragment-container');
        const mask = {container: configuredContainer, el: document.querySelector('.first-fragment-element')};

        proto.attach.call(mask);

        expect(attachedContainer).toBe(configuredContainer);
    });

    it('restores the configured container after attaching', () => {
        const mask = {container: document.body, el: document.querySelector('.first-fragment-element')};

        proto.attach.call(mask);

        expect(mask.container).toBe(document.body);
    });

    it('restores the configured container when attaching throws', () => {
        proto.attach = originalAttach;
        proto.attach = function() {
            throw new Error('cannot attach');
        };
        installLoadingMaskEmbeddedExtend();
        const mask = {container: document.body, el: document.querySelector('.first-fragment-element')};

        expect(() => proto.attach.call(mask)).toThrow();
        expect(mask.container).toBe(document.body);
    });
});
