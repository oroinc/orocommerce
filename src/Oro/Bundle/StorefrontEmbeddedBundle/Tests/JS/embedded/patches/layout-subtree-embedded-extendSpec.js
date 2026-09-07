import $ from 'jquery';
import 'jasmine-jquery';
import layoutSubtreeManager from 'oroui/js/layout-subtree-manager';
import {
    installLayoutSubtreeEmbeddedExtend
} from 'orostorefrontembedded/js/embedded/patches/layout-subtree-embedded-extend';
import html from 'text-loader!./Fixture/two-fragments-with-subtree.html';

describe('orostorefrontembedded/js/embedded/patches/layout-subtree-embedded-extend', () => {
    const HOST_URL = '/host-layout-subtree';
    const FIRST_BLOCK_URL = '/oro-embedded-fragment/embedded_orders';
    const SECOND_BLOCK_URL = '/oro-embedded-fragment/embedded_invoices';

    let originalAddView;
    let originalRemoveView;
    let originalUrl;
    let usedManagers;

    const createView = selector => ({$el: $(selector), options: {layoutSubtreeParent: ''}});

    beforeEach(() => {
        window.setFixtures(html);

        originalAddView = layoutSubtreeManager.addView;
        originalRemoveView = layoutSubtreeManager.removeView;
        originalUrl = layoutSubtreeManager.url;
        layoutSubtreeManager.url = HOST_URL;

        usedManagers = [];
        layoutSubtreeManager.addView = function() {
            usedManagers.push(this);
        };
        layoutSubtreeManager.removeView = function() {
            usedManagers.push(this);
        };
        installLayoutSubtreeEmbeddedExtend();
    });

    afterEach(() => {
        layoutSubtreeManager.addView = originalAddView;
        layoutSubtreeManager.removeView = originalRemoveView;
        layoutSubtreeManager.url = originalUrl;
    });

    it('reloads a subtree from the url of its own fragment', () => {
        layoutSubtreeManager.addView(createView('.first-fragment .subtree'));

        expect(usedManagers[0].url).toBe(FIRST_BLOCK_URL);
    });

    it('uses a separate manager per fragment', () => {
        layoutSubtreeManager.addView(createView('.first-fragment .subtree'));
        layoutSubtreeManager.addView(createView('.second-fragment .subtree'));

        expect(usedManagers[0].url).toBe(FIRST_BLOCK_URL);
        expect(usedManagers[1].url).toBe(SECOND_BLOCK_URL);
        expect(usedManagers[0]).not.toBe(usedManagers[1]);
    });

    it('reuses the manager of a fragment for its other views', () => {
        layoutSubtreeManager.addView(createView('.first-fragment .subtree'));
        layoutSubtreeManager.removeView(createView('.first-fragment .subtree'));

        expect(usedManagers[1]).toBe(usedManagers[0]);
    });

    it('uses the host manager for a view outside a fragment', () => {
        layoutSubtreeManager.addView(createView('.host-subtree'));

        expect(usedManagers[0]).toBe(layoutSubtreeManager);
        expect(usedManagers[0].url).toBe(HOST_URL);
    });

    it('uses the host url for a fragment without a subtree url', () => {
        layoutSubtreeManager.addView(createView('.fragment-without-subtree-url .subtree'));

        expect(usedManagers[0].url).toBe(HOST_URL);
    });
});
