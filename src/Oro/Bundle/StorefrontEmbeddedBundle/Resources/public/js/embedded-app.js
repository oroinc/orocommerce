import {bootEmbeddedApp} from './embedded/embedded';
import {createFragment} from './embedded/fragment';
import {configureRuntime} from './embedded/host';
import {announceFragmentRendered} from './embedded/render-completion';
import {showFlashMessages} from './embedded/flash-messages';

function isAborted({signal}) {
    return signal?.aborted === true;
}

async function init(containerEl, options = {}) {
    configureRuntime(options);

    await bootEmbeddedApp();

    if (isAborted(options)) {
        return {components: [], dispose: () => {}};
    }

    const fragment = createFragment(containerEl, options.navigate);

    try {
        const components = await fragment.start();

        if (isAborted(options)) {
            fragment.dispose();

            return {components, dispose: () => fragment.dispose()};
        }

        await announceFragmentRendered(components);

        showFlashMessages(options.flashMessages);

        return {components, dispose: () => fragment.dispose()};
    } catch (error) {
        fragment.dispose();
        throw error;
    }
}

if (window.OroEmbeddedFragmentRuntime) {
    console.warn('[oro-embedded-fragment] runtime already loaded; ignoring duplicate load');
} else {
    window.OroEmbeddedFragmentRuntime = {init};
}

export {init};
