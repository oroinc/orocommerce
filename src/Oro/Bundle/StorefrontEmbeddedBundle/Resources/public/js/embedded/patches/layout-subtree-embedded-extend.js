import {wrap} from 'underscore';
import layoutSubtreeManager from 'oroui/js/layout-subtree-manager';
import {EMBEDDED_MARKER_ATTRIBUTE, SUBTREE_URL_ATTRIBUTE} from '../config';

const FRAGMENT_MANAGER_DATA_KEY = 'oro.embeddedFragmentSubtreeManager';

export function installLayoutSubtreeEmbeddedExtend() {
    layoutSubtreeManager.addView = wrap(layoutSubtreeManager.addView, function(addView, view) {
        return addView.call(managerForView(layoutSubtreeManager, view), view);
    });

    layoutSubtreeManager.removeView = wrap(layoutSubtreeManager.removeView, function(removeView, view) {
        return removeView.call(managerForView(layoutSubtreeManager, view), view);
    });
}

function managerForView(layoutSubtreeManager, view) {
    const $fragment = view.$el?.closest(`[${EMBEDDED_MARKER_ATTRIBUTE}]`);

    if (!$fragment?.length) {
        return layoutSubtreeManager;
    }

    if (!$fragment.data(FRAGMENT_MANAGER_DATA_KEY)) {
        $fragment.data(FRAGMENT_MANAGER_DATA_KEY, createFragmentManager(layoutSubtreeManager, $fragment));
    }

    return $fragment.data(FRAGMENT_MANAGER_DATA_KEY);
}

function createFragmentManager(layoutSubtreeManager, $fragment) {
    const manager = Object.create(layoutSubtreeManager);

    manager.viewsCollection = {};
    manager.reloadEvents = {};

    Object.defineProperty(manager, 'url', {
        get: () => $fragment.attr(SUBTREE_URL_ATTRIBUTE) ?? layoutSubtreeManager.url
    });

    return manager;
}
