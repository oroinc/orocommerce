import $ from 'jquery';
import {wrap} from 'underscore';
import 'oroui/js/extend/bootstrap/bootstrap-popover';
import {overlayRootInsteadOfBody} from '../active-fragment';

export function installTooltipEmbeddedExtend() {
    const proto = $.fn.tooltip.Constructor.prototype;

    proto._getContainer = wrap(proto._getContainer, function(getContainer, ...args) {
        return overlayRootInsteadOfBody(getContainer.apply(this, args), this.element);
    });
}
