import $ from 'jquery';
import {wrap} from 'underscore';
import routing from 'routing';
import {isProxiedRoute, toProxiedPath} from '../host';

export function installRequestProxy() {
    $.ajaxPrefilter(options => {
        options.url = toProxiedPath(options.url || '');
    });

    routing.generate = wrap(routing.generate, function(generate, name, ...rest) {
        const url = generate.call(this, name, ...rest);

        return isProxiedRoute(name) ? toProxiedPath(url) : url;
    });
}
