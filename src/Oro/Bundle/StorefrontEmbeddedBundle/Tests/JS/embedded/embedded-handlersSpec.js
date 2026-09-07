import 'jasmine-jquery';
import mediator from 'oroui/js/mediator';
import {clearActiveFragment, setActiveFragment} from 'orostorefrontembedded/js/embedded/active-fragment';
import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import {registerEmbeddedHandlers} from 'orostorefrontembedded/js/embedded/embedded-handlers';
import html from 'text-loader!./Fixture/two-fragments.html';

describe('orostorefrontembedded/js/embedded/embedded-handlers', () => {
    const ORO = 'http://oro.loc';
    let fragment;
    let updateQueryParams;

    beforeEach(() => {
        window.setFixtures(html);

        updateQueryParams = jasmine.createSpy('updateQueryParams');
        configureRuntime({
            proxyPrefix: '/__oro',
            oroBaseUrl: ORO,
            filePaths: ['/pdf-document/download/'],
            updateQueryParams
        });
        registerEmbeddedHandlers();

        fragment = {
            containerEl: document.querySelector('.first-fragment-container'),
            overlayRoot: document.querySelector('.first-fragment-overlay'),
            loadingMask: jasmine.createSpyObj('loadingMask', ['show', 'hide']),
            reload: jasmine.createSpy('reload'),
            navigate: jasmine.createSpy('navigate').and.returnValue(true)
        };
        setActiveFragment(fragment);
    });

    afterEach(() => clearActiveFragment(fragment));

    describe('the url handlers', () => {
        it('returns a path without the leading slash', () => {
            expect(mediator.execute('retrievePath', '/customer/order/')).toBe('customer/order/');
            expect(mediator.execute('retrievePath', 'customer/order/')).toBe('customer/order/');
        });

        it('returns an absolute url for a path without a leading slash', () => {
            expect(mediator.execute('combineFullUrl', 'customer/order/', 'page=2')).toBe('/customer/order/?page=2');
        });

        it('returns no page option, because a fragment is not a page', () => {
            expect(mediator.execute('retrieveOption', 'anything')).toBeUndefined();
        });
    });

    describe('showLoading and hideLoading', () => {
        it('shows the loading mask of the active fragment', () => {
            mediator.execute('showLoading');

            expect(fragment.loadingMask.show).toHaveBeenCalled();
        });

        it('hides the loading mask of the active fragment', () => {
            mediator.execute('hideLoading');

            expect(fragment.loadingMask.hide).toHaveBeenCalled();
        });

        it('shows the loading mask on page:beforeChange and hides it on page:afterChange', () => {
            mediator.trigger('page:beforeChange');
            expect(fragment.loadingMask.show).toHaveBeenCalled();

            mediator.trigger('page:afterChange');
            expect(fragment.loadingMask.hide).toHaveBeenCalled();
        });
    });

    describe('redirectTo', () => {
        it('navigates the host to the requested url', () => {
            mediator.execute('redirectTo', {url: '/customer/order/view/44'});

            expect(fragment.navigate).toHaveBeenCalledWith('/customer/order/view/44', {newTab: false});
        });

        it('navigates in a new tab for a _blank target', () => {
            mediator.execute('redirectTo', {url: '/customer/order/view/44'}, {target: '_blank'});

            expect(fragment.navigate).toHaveBeenCalledWith('/customer/order/view/44', {newTab: true});
        });

        it('opens a page the host has no route for, instead of standing still', () => {
            fragment.navigate.and.returnValue(false);
            spyOn(window, 'open');

            mediator.execute('redirectTo', {url: '/customer/checkout/1'}, {target: '_blank'});

            expect(window.open).toHaveBeenCalledWith('/customer/checkout/1', '_blank');
            expect(fragment.loadingMask.hide).not.toHaveBeenCalled();
        });

        it('leaves the loading mask alone when the browser opens a file the host does not open', () => {
            fragment.navigate.and.returnValue(false);
            spyOn(window, 'open');

            mediator.execute('redirectTo', {url: '/pdf-document/download/44'}, {target: '_blank'});

            expect(window.open).toHaveBeenCalledWith('/__oro/pdf-document/download/44', '_blank');
            expect(fragment.loadingMask.hide).not.toHaveBeenCalled();
        });

        it('stays on the host for an absolute Oro url, instead of leaving for the Oro origin', () => {
            fragment.navigate.and.returnValue(false);
            spyOn(window, 'open');

            mediator.execute('redirectTo', {url: `${ORO}/customer/checkout/1`}, {target: '_blank'});

            expect(fragment.navigate).toHaveBeenCalledWith('/customer/checkout/1', {newTab: true});
            expect(window.open).toHaveBeenCalledWith('/customer/checkout/1', '_blank');
        });

        it('opens the file of an absolute Oro url on the proxied path', () => {
            fragment.navigate.and.returnValue(false);
            spyOn(window, 'open');

            mediator.execute('redirectTo', {url: `${ORO}/pdf-document/download/44`}, {target: '_blank'});

            expect(window.open).toHaveBeenCalledWith('/__oro/pdf-document/download/44', '_blank');
        });

        it('navigates to a payment provider on another origin', () => {
            fragment.navigate.and.returnValue(false);
            spyOn(window, 'open');

            mediator.execute('redirectTo', {url: 'https://payment.example/pay/44'}, {target: '_blank'});

            expect(window.open).toHaveBeenCalledWith('https://payment.example/pay/44', '_blank');
        });

        it('does not navigate when no url is given', () => {
            mediator.execute('redirectTo', {});

            expect(fragment.navigate).not.toHaveBeenCalled();
        });
    });

    it('reloads the active fragment', () => {
        mediator.execute('refreshPage');

        expect(fragment.reload).toHaveBeenCalled();
    });

    describe('changeRoute', () => {
        it('shows the query of the changed route on the host url', () => {
            mediator.execute('changeRoute', {path: '/customer/invoice/', query: 'grid[invoices]=i%3D2'});

            expect(updateQueryParams).toHaveBeenCalledWith('grid[invoices]=i%3D2');
        });

        it('shows an empty query for a route without one', () => {
            mediator.execute('changeRoute', {path: '/customer/invoice/'});

            expect(updateQueryParams).toHaveBeenCalledWith('');
        });
    });
});
