import {bootEmbeddedApp} from './embedded/embedded';
import {createFragment} from './embedded/fragment';
import {configureRuntime} from './embedded/host';
import {announceFragmentRendered} from './embedded/render-completion';
import {showFlashMessages} from './embedded/flash-messages';

async function init(containerEl, options = {}) {
    configureRuntime(options);

    await bootEmbeddedApp();

    const fragment = createFragment(containerEl, options.navigate);

    try {
        const components = await fragment.start();

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
