import {wrap} from 'underscore';
import DomRelocationView from 'orofrontend/js/app/views/dom-relocation-view';
import {EMBEDDED_MARKER_ATTRIBUTE, RELOCATION_TARGET_ATTRIBUTE} from '../config';

export function installDomRelocationEmbeddedExtend() {
    const proto = DomRelocationView.prototype;

    proto.moveToTarget = wrap(proto.moveToTarget, function(moveToTarget, $el, targetOptions) {
        const targetInSameFragment = $el
            .closest(`[${EMBEDDED_MARKER_ATTRIBUTE}]`)
            .find(targetOptions.moveTo)
            .first();

        if (!targetInSameFragment.length) {
            return moveToTarget.call(this, $el, targetOptions);
        }

        targetInSameFragment.attr(RELOCATION_TARGET_ATTRIBUTE, '');

        try {
            return moveToTarget.call(this, $el, {
                ...targetOptions,
                moveTo: `[${RELOCATION_TARGET_ATTRIBUTE}]`
            });
        } finally {
            targetInSameFragment.removeAttr(RELOCATION_TARGET_ATTRIBUTE);
        }
    });
}
