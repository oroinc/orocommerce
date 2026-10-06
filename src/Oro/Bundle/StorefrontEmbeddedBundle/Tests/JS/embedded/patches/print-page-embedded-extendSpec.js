import $ from 'jquery';
import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import {installPrintPageEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/print-page-embedded-extend';

describe('orostorefrontembedded/js/embedded/patches/print-page-embedded-extend', () => {
    const proto = $.oroui.printPageWidget.prototype;

    let originalIFramePrint;
    let iFramePrint;
    let printFrame;
    let printFrameFetch;

    beforeEach(() => {
        configureRuntime({proxyPrefix: '/__oro', oroBaseUrl: 'http://oro.loc'});
        originalIFramePrint = proto._iFramePrint;
        iFramePrint = jasmine.createSpy('_iFramePrint').and.callFake(() => {
            printFrame = document.createElement('iframe');
            document.body.appendChild(printFrame);
            printFrameFetch = jasmine.createSpy('fetch');
            printFrame.contentWindow.fetch = printFrameFetch;
        });
        proto._iFramePrint = iFramePrint;
        installPrintPageEmbeddedExtend();
    });

    afterEach(() => {
        printFrame.remove();
        proto._iFramePrint = originalIFramePrint;
    });

    it('prints the proxied route', () => {
        proto._iFramePrint.call({}, '/customer/order/print/44');

        expect(iFramePrint).toHaveBeenCalledWith('/__oro/customer/order/print/44');
    });

    it('prints a route that already carries the proxy prefix unchanged', () => {
        proto._iFramePrint.call({}, '/__oro/customer/order/print/44');

        expect(iFramePrint).toHaveBeenCalledWith('/__oro/customer/order/print/44');
    });

    it('sends the root-relative fetches of the print page through the host proxy', () => {
        const options = {credentials: 'same-origin'};
        proto._iFramePrint.call({}, '/customer/order/print/44');

        printFrame.contentWindow.fetch('/media/js/translation/en.json?v=1', options);

        expect(printFrameFetch).toHaveBeenCalledOnceWith('/__oro/media/js/translation/en.json?v=1', options);
        expect(printFrameFetch.calls.mostRecent().object).toBe(printFrame.contentWindow);
    });

    it('leaves the fetches of the print page to other origins as they are', () => {
        proto._iFramePrint.call({}, '/customer/order/print/44');

        printFrame.contentWindow.fetch('http://oro.loc/media/js/translation/en.json');

        expect(printFrameFetch).toHaveBeenCalledOnceWith('http://oro.loc/media/js/translation/en.json', undefined);
    });

    it('loads the print page images under /bundles straight from Oro once the page is parsed', () => {
        proto._iFramePrint.call({}, '/customer/order/print/44');
        const printDocument = printFrame.contentDocument;
        printDocument.body.innerHTML = `
            <picture>
                <source id="small-logo" srcset="/bundles/orofrontend/images/logo-small.svg?v=1">
                <img id="logo" src="/bundles/orofrontend/images/logo.svg?v=1" alt="">
            </picture>`;

        printFrame.contentWindow.dispatchEvent(new Event('DOMContentLoaded'));

        expect(printDocument.getElementById('small-logo').getAttribute('srcset'))
            .toBe('http://oro.loc/bundles/orofrontend/images/logo-small.svg?v=1');
        expect(printDocument.getElementById('logo').getAttribute('src'))
            .toBe('http://oro.loc/bundles/orofrontend/images/logo.svg?v=1');
    });

    it('leaves the print page images outside /bundles as they are', () => {
        proto._iFramePrint.call({}, '/customer/order/print/44');
        const printDocument = printFrame.contentDocument;
        printDocument.body.innerHTML = `
            <img id="icon" src="/build/intelligent_blue/images/icon.svg" alt="">
            <img id="product" src="http://oro.loc/media/cache/product.jpg" alt="">`;

        printFrame.contentWindow.dispatchEvent(new Event('DOMContentLoaded'));

        expect(printDocument.getElementById('icon').getAttribute('src'))
            .toBe('/build/intelligent_blue/images/icon.svg');
        expect(printDocument.getElementById('product').getAttribute('src'))
            .toBe('http://oro.loc/media/cache/product.jpg');
    });
});
