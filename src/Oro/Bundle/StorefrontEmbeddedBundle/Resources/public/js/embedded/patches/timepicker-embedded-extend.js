import $ from 'jquery';
import {wrap} from 'underscore';
import 'jquery.timepicker';
import {getActiveFragment} from '../active-fragment';

export function installTimepickerEmbeddedExtend() {
    $.fn.timepicker = wrap($.fn.timepicker, function(timepicker, method, ...args) {
        if (typeof method === 'object' || !method || method === 'init') {
            const options = {appendTo: activeFragmentOverlayRoot, ...(method === 'init' ? args[1] : method)};

            return method === 'init'
                ? timepicker.call(this, method, args[0], options)
                : timepicker.call(this, options);
        }

        return timepicker.call(this, method, ...args);
    });
}

function activeFragmentOverlayRoot() {
    return $(getActiveFragment()?.overlayRoot ?? document.body);
}
