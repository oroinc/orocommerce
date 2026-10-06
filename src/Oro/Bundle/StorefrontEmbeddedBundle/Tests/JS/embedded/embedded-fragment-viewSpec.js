import 'jasmine-jquery';
import mediator from 'oroui/js/mediator';
import EmbeddedFragmentView from 'orostorefrontembedded/js/embedded/embedded-fragment-view';
import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import html from 'text-loader!./Fixture/fragment-with-links-and-forms.html';

function triggerEvent(target, event) {
    let defaultPrevented = null;
    const recordDefaultPrevented = seen => {
        defaultPrevented = seen.defaultPrevented;
    };
    const preventNavigation = seen => seen.preventDefault();

    document.body.addEventListener(event.type, recordDefaultPrevented);
    document.addEventListener(event.type, preventNavigation);
    try {
        target.dispatchEvent(event);
    } finally {
        document.body.removeEventListener(event.type, recordDefaultPrevented);
        document.removeEventListener(event.type, preventNavigation);
    }

    return {target, defaultPrevented};
}

describe('orostorefrontembedded/js/embedded/embedded-fragment-view', () => {
    const ANOTHER_PAGE = '/customer/order/';
    const DOWNLOAD = '/customer/order/pdf-document/44/download';

    let view;
    let fragment;
    let el;

    beforeEach(() => {
        configureRuntime({
            isAnotherPage: url => url === ANOTHER_PAGE,
            oroBaseUrl: window.location.origin,
            proxyPrefix: '/__oro'
        });

        fragment = {
            loadingMask: jasmine.createSpyObj('loadingMask', ['show', 'hide']),
            reload: jasmine.createSpy('reload').and.returnValue(Promise.resolve()),
            navigate: jasmine.createSpy('navigate').and.returnValue(true)
        };
        window.setFixtures(html);
        el = document.querySelector('.fragment-root');
        view = new EmbeddedFragmentView({el, fragment});
    });

    afterEach(() => view.dispose());

    describe('goTo', () => {
        it('shows and hides the loading mask while the fragment reloads', async () => {
            await view.goTo(window.location.pathname + window.location.search);

            expect(fragment.reload).toHaveBeenCalled();
            expect(fragment.loadingMask.show).toHaveBeenCalled();
            expect(fragment.loadingMask.hide).toHaveBeenCalled();
        });

        it('shows the loading mask when the host opens another page', () => {
            view.goTo(ANOTHER_PAGE);

            expect(fragment.navigate).toHaveBeenCalledWith(ANOTHER_PAGE, {});
            expect(fragment.loadingMask.show).toHaveBeenCalled();
        });

        it('does not show the loading mask for a url the host does not open as a page', () => {
            view.goTo(DOWNLOAD);

            expect(fragment.loadingMask.show).not.toHaveBeenCalled();
            expect(fragment.loadingMask.hide).toHaveBeenCalled();
        });

        it('does not show the loading mask when only the query changes', () => {
            view.goTo(`${window.location.pathname}?grid[row-view]=gallery-view`);

            expect(fragment.loadingMask.show).not.toHaveBeenCalled();
            expect(fragment.loadingMask.hide).toHaveBeenCalled();
        });
    });

    describe('onLink', () => {
        const click = selector => triggerEvent(
            el.querySelector(selector),
            new MouseEvent('click', {bubbles: true, cancelable: true, button: 0})
        );

        beforeEach(() => spyOn(view, 'goTo'));

        it('navigates the host to an internal link', () => {
            click('.internal-link');

            expect(view.goTo).toHaveBeenCalledWith('/customer/order/view/44');
        });

        it('does not handle a link with a download attribute', () => {
            click('.download-link');

            expect(view.goTo).not.toHaveBeenCalled();
        });

        it('does not handle a link with a _blank target', () => {
            click('.blank-target-link');

            expect(view.goTo).not.toHaveBeenCalled();
        });

        it('handles a link with a _self target', () => {
            click('.self-target-link');

            expect(view.goTo).toHaveBeenCalled();
        });

        it('does not handle a link to another origin', () => {
            click('.external-link');

            expect(view.goTo).not.toHaveBeenCalled();
        });

        it('prevents the default action of a hash link', () => {
            const {defaultPrevented} = click('.hash-link');

            expect(view.goTo).not.toHaveBeenCalled();
            expect(defaultPrevented).toBe(true);
        });

        it('does not handle a link matching the skipRouting selector', () => {
            click('.noscript');

            expect(view.goTo).not.toHaveBeenCalled();
        });

        it('does not handle a link prevented by an openLink:before subscriber', () => {
            const claimLink = payload => {
                payload.prevented = true;
            };
            mediator.on('openLink:before', claimLink);

            click('.internal-link');
            mediator.off('openLink:before', claimLink);

            expect(view.goTo).not.toHaveBeenCalled();
        });

        it('reads the url from data-href when the href is empty', () => {
            click('.data-href-link');

            expect(view.goTo).toHaveBeenCalledWith('/customer/order/view/44');
        });
    });

    describe('onSubmit', () => {
        const submit = selector => triggerEvent(
            el.querySelector(selector),
            new Event('submit', {bubbles: true, cancelable: true})
        );

        beforeEach(() => {
            spyOn(view, 'goTo');
            spyOn(view, 'submitForm');
        });

        it('navigates to the action with the serialized query for a get form', () => {
            submit('.get-form');

            expect(view.goTo).toHaveBeenCalledWith('/customer/order/?search=lamp');
        });

        it('submits a post form over ajax', () => {
            submit('.post-form');

            expect(view.submitForm).toHaveBeenCalled();
            expect(view.goTo).not.toHaveBeenCalled();
        });

        it('submits to the current path for a form without an action', () => {
            submit('.form-without-action');

            expect(view.goTo).toHaveBeenCalledWith(`${window.location.pathname}?search=lamp`);
        });

        it('does not handle a form with data-prevent-submit', () => {
            submit('.prevent-submit-form');

            expect(view.goTo).not.toHaveBeenCalled();
            expect(view.submitForm).not.toHaveBeenCalled();
        });

        it('does not handle a form with data-nohash', () => {
            submit('.nohash-form');

            expect(view.goTo).not.toHaveBeenCalled();
        });

        it('does not handle a form posting to another origin', () => {
            submit('.external-post-form');

            expect(view.submitForm).not.toHaveBeenCalled();
        });
    });
});
