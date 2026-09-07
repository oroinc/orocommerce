import $ from 'jquery';
import mediator from 'oroui/js/mediator';

export async function announceFragmentRendered(components) {
    await whenViewsStopRendering(components);
    mediator.trigger('page:afterChange');
}

function pendingRenders(components) {
    return components
        .map(component => component?.view?.getDeferredRenderPromise?.())
        .filter(Boolean);
}

function whenViewsStopRendering(components) {
    return new Promise(resolve => {
        const waitForCurrentRound = () => {
            const promises = pendingRenders(components);
            if (!promises.length) {
                resolve();
                return;
            }
            $.when(...promises).always(waitForCurrentRound);
        };

        waitForCurrentRound();
    });
}
