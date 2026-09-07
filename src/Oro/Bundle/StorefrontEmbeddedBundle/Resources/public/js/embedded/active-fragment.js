import $ from 'jquery';

let liveFragments = [];

export function setActiveFragment(fragment) {
    clearActiveFragment(fragment);
    liveFragments.push(fragment);
}

export function clearActiveFragment(fragment) {
    liveFragments = liveFragments.filter(live => live !== fragment);
}

export function getActiveFragment() {
    return liveFragments.at(-1) ?? null;
}

export function findOverlayRoot(node) {
    return (findFragmentFor(node) ?? getActiveFragment())?.overlayRoot ?? null;
}

export function isBodyContainer(container) {
    return $(container)[0] === document.body;
}

export function overlayRootInsteadOfBody(container, nodeInFragment) {
    if (!isBodyContainer(container)) {
        return container;
    }

    return findOverlayRoot(nodeInFragment) ?? container;
}

function findFragmentFor(node) {
    if (!node) {
        return null;
    }

    return liveFragments.find(
        fragment => fragment.containerEl.contains(node) || fragment.overlayRoot.contains(node)
    ) ?? null;
}
