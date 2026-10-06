import $ from 'jquery';
import {wrap} from 'underscore';
import 'orofrontend/default/js/widgets/print-page-widget';
import {getOroBaseUrl, toProxiedPath} from '../host';

const BUNDLE_IMAGE_ATTRIBUTES = [['img[src^="/bundles/"]', 'src'], ['source[srcset^="/bundles/"]', 'srcset']];

export function installPrintPageEmbeddedExtend() {
    const proto = $.oroui.printPageWidget.prototype;

    proto._iFramePrint = wrap(proto._iFramePrint, function(iFramePrint, route) {
        iFramePrint.call(this, toProxiedPath(route));

        const printFrame = document.body.lastElementChild;
        proxyFetch(printFrame.contentWindow);
        loadImagesFromOro(printFrame.contentWindow);
    });
}

function proxyFetch(frameWindow) {
    const frameFetch = frameWindow.fetch;

    frameWindow.fetch = (resource, options) => frameFetch.call(frameWindow, toProxiedPath(resource), options);
}

function loadImagesFromOro(frameWindow) {
    frameWindow.addEventListener('DOMContentLoaded', () => {
        const oroOrigin = new URL(getOroBaseUrl()).origin;

        for (const [selector, attribute] of BUNDLE_IMAGE_ATTRIBUTES) {
            for (const image of frameWindow.document.querySelectorAll(selector)) {
                image.setAttribute(attribute, oroOrigin + image.getAttribute(attribute));
            }
        }
    }, {once: true});
}
