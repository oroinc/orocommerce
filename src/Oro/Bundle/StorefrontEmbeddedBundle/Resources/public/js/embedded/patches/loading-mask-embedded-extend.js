import {wrap} from 'underscore';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import {overlayRootInsteadOfBody} from '../active-fragment';

export function installLoadingMaskEmbeddedExtend() {
    const proto = LoadingMaskView.prototype;

    proto.attach = wrap(proto.attach, function(attach, ...args) {
        const configuredContainer = this.container;
        this.container = overlayRootInsteadOfBody(configuredContainer, this.el);

        try {
            return attach.apply(this, args);
        } finally {
            this.container = configuredContainer;
        }
    });
}
