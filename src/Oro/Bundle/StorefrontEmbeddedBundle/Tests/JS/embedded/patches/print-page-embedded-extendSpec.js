import $ from 'jquery';
import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import {installPrintPageEmbeddedExtend} from 'orostorefrontembedded/js/embedded/patches/print-page-embedded-extend';

describe('orostorefrontembedded/js/embedded/patches/print-page-embedded-extend', () => {
    const proto = $.oroui.printPageWidget.prototype;

    let originalIFramePrint;
    let iFramePrint;

    beforeEach(() => {
        configureRuntime({proxyPrefix: '/__oro'});
        originalIFramePrint = proto._iFramePrint;
        iFramePrint = jasmine.createSpy('_iFramePrint');
        proto._iFramePrint = iFramePrint;
        installPrintPageEmbeddedExtend();
    });

    afterEach(() => {
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
});
