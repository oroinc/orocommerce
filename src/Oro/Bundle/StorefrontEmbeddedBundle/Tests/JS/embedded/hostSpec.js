import {
    combineRouteUrl,
    configureRuntime,
    isProxiedRoute,
    navigateFragmentTo,
    toProxiedPath
} from 'orostorefrontembedded/js/embedded/host';

describe('orostorefrontembedded/js/embedded/host', () => {
    describe('toProxiedPath', () => {
        beforeEach(() => configureRuntime({proxyPrefix: '/__oro'}));

        it('prefixes an Oro path with the proxy prefix', () => {
            expect(toProxiedPath('/customer/order/pdf-document/44/download'))
                .toBe('/__oro/customer/order/pdf-document/44/download');
        });

        it('does not prefix a path that already carries the proxy prefix', () => {
            expect(toProxiedPath('/__oro/customer/order/')).toBe('/__oro/customer/order/');
        });

        it('returns a protocol-relative url unchanged', () => {
            expect(toProxiedPath('//other.example/x')).toBe('//other.example/x');
        });

        it('returns an absolute url unchanged', () => {
            expect(toProxiedPath('http://oro.loc/customer/order/')).toBe('http://oro.loc/customer/order/');
        });

        it('returns a value that is not a string unchanged', () => {
            expect(toProxiedPath(undefined)).toBeUndefined();
        });

        it('returns the path unchanged when no proxy prefix is configured', () => {
            configureRuntime({proxyPrefix: ''});

            expect(toProxiedPath('/customer/order/')).toBe('/customer/order/');
        });
    });

    describe('combineRouteUrl', () => {
        it('returns an absolute path for a path without a leading slash', () => {
            expect(combineRouteUrl('customer/order/', 'page=2')).toBe('/customer/order/?page=2');
        });

        it('returns the path and query of a route object', () => {
            expect(combineRouteUrl({path: '/customer/order/', query: 'page=2'})).toBe('/customer/order/?page=2');
        });

        it('returns the path alone for a route without a query', () => {
            expect(combineRouteUrl({path: '/customer/order/'})).toBe('/customer/order/');
        });
    });

    describe('isProxiedRoute', () => {
        it('returns true only for the configured routes', () => {
            configureRuntime({proxiedRoutes: ['oro_spec_price_by_customer']});

            expect(isProxiedRoute('oro_spec_price_by_customer')).toBe(true);
            expect(isProxiedRoute('oro_spec_route_nobody_reported')).toBe(false);
        });
    });

    describe('navigateFragmentTo', () => {
        const ORO_FILE_PATHS = [
            '/attachment/download/',
            '/pdf-document/download/',
            '/customer/order/pdf-document/'
        ];

        let fragment;

        beforeEach(() => {
            configureRuntime({proxyPrefix: '/__oro', filePaths: ORO_FILE_PATHS});
            spyOn(window, 'open');
            fragment = {navigate: jasmine.createSpy('navigate')};
        });

        it('does not open a window when the host handles the navigation', () => {
            fragment.navigate.and.returnValue(true);

            expect(navigateFragmentTo(fragment, '/customer/order/', {newTab: true})).toBe(false);

            expect(fragment.navigate).toHaveBeenCalledWith('/customer/order/', {newTab: true});
            expect(window.open).not.toHaveBeenCalled();
        });

        it('opens a file the host does not handle on the proxied path', () => {
            fragment.navigate.and.returnValue(false);

            expect(navigateFragmentTo(fragment, '/customer/order/pdf-document/44/download', {newTab: true}))
                .toBe(false);

            expect(window.open).toHaveBeenCalledWith('/__oro/customer/order/pdf-document/44/download', '_blank');
        });

        it('opens an attachment the host does not handle on the proxied path', () => {
            fragment.navigate.and.returnValue(false);

            navigateFragmentTo(fragment, '/attachment/download/44/invoice.pdf', {newTab: true});

            expect(window.open).toHaveBeenCalledWith('/__oro/attachment/download/44/invoice.pdf', '_blank');
        });

        it('opens a file on the proxied path when no fragment is given', () => {
            navigateFragmentTo(null, '/pdf-document/download/8ae5ee9c-2f0e-4c67-bf0c-2b1a2f1f0b3e', {newTab: true});

            expect(window.open)
                .toHaveBeenCalledWith('/__oro/pdf-document/download/8ae5ee9c-2f0e-4c67-bf0c-2b1a2f1f0b3e', '_blank');
        });

        it('opens a page the host has no route for on the host origin, not through the Oro proxy', () => {
            fragment.navigate.and.returnValue(false);

            navigateFragmentTo(fragment, '/customer/checkout/1', {newTab: true});

            expect(window.open).toHaveBeenCalledWith('/customer/checkout/1', '_blank');
        });

        it('opens a page the host has no route for when no fragment is given', () => {
            navigateFragmentTo(null, '/400-watt-bulb-work-light', {newTab: true});

            expect(window.open).toHaveBeenCalledWith('/400-watt-bulb-work-light', '_blank');
        });

        it('opens a url of another origin, which is no Oro page', () => {
            fragment.navigate.and.returnValue(false);

            navigateFragmentTo(fragment, 'https://payment.example/pay/44', {newTab: true});

            expect(window.open).toHaveBeenCalledWith('https://payment.example/pay/44', '_blank');
        });
    });
});
