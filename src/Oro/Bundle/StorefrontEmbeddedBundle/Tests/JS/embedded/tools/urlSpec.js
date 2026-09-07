import {configureRuntime} from 'orostorefrontembedded/js/embedded/host';
import {isCurrentPath, toHostPath, toInternalPath, withQuery} from 'orostorefrontembedded/js/embedded/tools/url';

describe('orostorefrontembedded/js/embedded/tools/url', () => {
    const ORO = 'http://oro.loc';

    describe('toInternalPath', () => {
        beforeEach(() => configureRuntime({oroBaseUrl: ORO, proxyPrefix: '/__oro'}));

        it('keeps a path Oro rendered as it is', () => {
            expect(toInternalPath('/customer/order/view/44')).toBe('/customer/order/view/44');
        });

        it('turns an absolute Oro url into its path, since the host serves it under its own origin', () => {
            expect(toInternalPath(`${ORO}/customer/order/?page=2`)).toBe('/customer/order/?page=2');
        });

        it('refuses a url that leaves Oro', () => {
            expect(toInternalPath('http://other.example/customer/order/')).toBeNull();
        });

        it('refuses a protocol-relative url, which reads as a path but is another origin', () => {
            expect(toInternalPath('//other.example/customer/order/')).toBeNull();
        });

        it('refuses a url with no origin to compare', () => {
            expect(toInternalPath('')).toBeNull();
            expect(toInternalPath('mailto:sales@example.com')).toBeNull();
        });

        it('drops the base path of an Oro installed under a sub-path', () => {
            configureRuntime({oroBaseUrl: `${ORO}/shop`});

            expect(toInternalPath(`${ORO}/shop/customer/order/`)).toBe('/customer/order/');
        });
    });

    describe('toHostPath', () => {
        beforeEach(() => configureRuntime({oroBaseUrl: ORO, proxyPrefix: '/__oro'}));

        it('takes the proxy prefix off, because the host owns that path without it', () => {
            expect(toHostPath('/__oro/customer/order/')).toBe('/customer/order/');
        });

        it('drops the block ids the fragment asked Oro for and keeps what the page needs', () => {
            expect(toHostPath('/customer/order/?layout_block_ids[]=embedded_orders&page=2'))
                .toBe('/customer/order/?page=2');
        });

        it('leaves nothing but the path when the block ids were the only parameters', () => {
            expect(toHostPath('/customer/order/?layout_block_ids[]=embedded_orders')).toBe('/customer/order/');
        });

        it('resolves an absolute url against the host', () => {
            expect(toHostPath(`${window.location.origin}/customer/order/`)).toBe('/customer/order/');
        });
    });

    describe('isCurrentPath', () => {
        const here = window.location.pathname + window.location.search;

        it('recognises the page the visitor is on', () => {
            expect(isCurrentPath(here)).toBe(true);
        });

        it('reads a trailing slash as the same page, the way Oro links to it', () => {
            expect(isCurrentPath(window.location.pathname + '/' + window.location.search)).toBe(true);
        });

        it('tells another page apart', () => {
            expect(isCurrentPath('/customer/order/view/44')).toBe(false);
        });
    });

    describe('withQuery', () => {
        it('replaces the query a form was serialized over', () => {
            expect(withQuery('/customer/order/?page=2', 'page=3')).toBe('/customer/order/?page=3');
        });

        it('leaves the path bare when a form serializes to nothing', () => {
            expect(withQuery('/customer/order/?page=2', '')).toBe('/customer/order/');
        });
    });
});
