import {wrap} from 'underscore';
import Dropdown from 'oroui/js/extend/bootstrap/bootstrap-dropdown';
import {overlayRootInsteadOfBody} from '../active-fragment';

export function installDropdownEmbeddedExtend() {
    const proto = Dropdown.prototype;
    const isAlreadyDisposed = dropdown => !dropdown._element;

    proto.dispose = wrap(proto.dispose, function(dispose, ...args) {
        if (isAlreadyDisposed(this)) {
            return;
        }

        return dispose.apply(this, args);
    });

    proto._getConfig = wrap(proto._getConfig, function(getConfig, ...args) {
        const config = getConfig.apply(this, args);

        return {...config, menuContainer: overlayRootInsteadOfBody(config.menuContainer, this._element)};
    });
}
