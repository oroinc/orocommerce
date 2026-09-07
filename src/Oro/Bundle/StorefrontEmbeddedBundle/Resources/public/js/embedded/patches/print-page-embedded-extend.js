import $ from 'jquery';
import {wrap} from 'underscore';
import 'orofrontend/default/js/widgets/print-page-widget';
import {toProxiedPath} from '../host';

export function installPrintPageEmbeddedExtend() {
    const proto = $.oroui.printPageWidget.prototype;

    proto._iFramePrint = wrap(proto._iFramePrint, function(iFramePrint, route) {
        return iFramePrint.call(this, toProxiedPath(route));
    });
}
