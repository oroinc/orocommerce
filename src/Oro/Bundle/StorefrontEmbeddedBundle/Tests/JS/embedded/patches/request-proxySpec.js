import $ from 'jquery';
import routing from 'routing';
import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import {installRequestProxy} from 'orostorefrontembedded/js/embedded/patches/request-proxy';

describe('orostorefrontembedded/js/embedded/patches/request-proxy', () => {
    const PROXIED_ROUTE = 'oro_spec_price_by_customer';
    const OWN_ROUTE = 'oro_spec_route_the_host_answers';

    let originalGenerate;

    beforeEach(() => {
        originalGenerate = routing.generate;
        routing.generate = (name, parameters) => `/${name}${parameters?.id ? `/${parameters.id}` : ''}`;
        installRequestProxy();
        configureRuntime({proxyPrefix: '/__oro', proxiedRoutes: [PROXIED_ROUTE]});
    });

    afterEach(() => {
        routing.generate = originalGenerate;
    });

    it('prefixes the url of a proxied route', () => {
        expect(routing.generate(PROXIED_ROUTE)).toBe(`/__oro/${PROXIED_ROUTE}`);
    });

    it('returns the url of any other route unchanged', () => {
        expect(routing.generate(OWN_ROUTE)).toBe(`/${OWN_ROUTE}`);
    });

    it('prefixes the url built out of the route parameters', () => {
        expect(routing.generate(PROXIED_ROUTE, {id: 44})).toBe(`/__oro/${PROXIED_ROUTE}/44`);
    });

    it('prefixes the url of an ajax request', () => {
        let requestedUrl = null;

        $.ajax({
            url: '/api/rest/latest/lines/99/item',
            beforeSend: (jqXHR, settings) => {
                requestedUrl = settings.url;

                return false;
            }
        });

        expect(requestedUrl).toBe('/__oro/api/rest/latest/lines/99/item');
    });
});
