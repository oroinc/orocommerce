import $ from 'jquery';
import {toProxiedPath} from '../host';

export function installRequestProxy() {
    $.ajaxPrefilter(options => {
        options.url = toProxiedPath(options.url || '');
    });
}
