import $ from 'jquery';
import {wrap} from 'underscore';
import 'jquery-ui/widgets/datepicker';
import {findOverlayRoot} from '../active-fragment';

export function installDatepickerEmbeddedExtend() {
    const placeCalendarFor = dateField => {
        const overlayRoot = findOverlayRoot(dateField);
        const calendar = $.datepicker.dpDiv[0];

        if (overlayRoot && calendar.parentElement !== overlayRoot) {
            overlayRoot.appendChild(calendar);
        }
    };

    const isPluginMethodCall = args => typeof args[0] === 'string';

    $.fn.datepicker = wrap($.fn.datepicker, function(datepicker, ...args) {
        if (this[0] && !isPluginMethodCall(args)) {
            placeCalendarFor(this[0]);
        }

        return datepicker.apply(this, args);
    });

    $.datepicker._showDatepicker = wrap(
        $.datepicker._showDatepicker,
        function(showDatepicker, input, ...rest) {
            placeCalendarFor(input.target ?? input);

            return showDatepicker.call(this, input, ...rest);
        }
    );
}
