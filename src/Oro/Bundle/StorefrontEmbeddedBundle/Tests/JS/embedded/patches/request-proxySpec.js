import $ from 'jquery';
import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import {installRequestProxy} from 'orostorefrontembedded/js/embedded/patches/request-proxy';

describe('orostorefrontembedded/js/embedded/patches/request-proxy', () => {
    beforeEach(() => {
        installRequestProxy();
        configureRuntime({proxyPrefix: '/__oro'});
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
