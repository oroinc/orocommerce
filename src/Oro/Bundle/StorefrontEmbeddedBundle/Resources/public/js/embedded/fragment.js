import $ from 'jquery';
import mediator from 'oroui/js/mediator';
import ComponentManager from 'oroui/js/app/components/component-manager';
import LoadingMaskView from 'oroui/js/app/views/loading-mask-view';
import EmbeddedFragmentView from './embedded-fragment-view';
import {setActiveFragment, clearActiveFragment, getActiveFragment} from './active-fragment';
import {createMessagesContainer, showFlashMessages, showMessagesIn} from './flash-messages';
import {
    BLOCK_ATTRIBUTE,
    PARAMS_ATTRIBUTE,
    EMBEDDED_FRAGMENT_ENDPOINT,
    EMBEDDED_MARKER_ATTRIBUTE,
    HASH_NAVIGATION_HEADER,
    OVERLAY_ROOT_CLASS
} from './config';

const INTERACTION_EVENTS = ['pointerdown', 'keydown'];

export function createFragment(containerEl, navigate = null) {
    markAsEmbeddedRoot(containerEl);

    const block = containerEl.getAttribute(BLOCK_ATTRIBUTE);
    const overlayRoot = createOverlayRoot();
    const messagesContainer = createMessagesContainer(overlayRoot);
    const interactionTracking = new AbortController();

    const fragment = {
        containerEl,
        overlayRoot,
        block,
        navigate,
        endpoint: blockEndpoint(block, containerEl.getAttribute(PARAMS_ATTRIBUTE) || ''),
        loadingMask: new LoadingMaskView({container: overlayRoot}),
        messagesContainer,
        manager: new ComponentManager($(containerEl)),
        disposed: false,

        initControls() {
            mediator.execute({name: 'layout:init', silent: true}, this.containerEl);
        },

        disposeControls() {
            mediator.execute({name: 'layout:dispose', silent: true}, this.containerEl);
        },

        async start() {
            const components = await this.manager.init();

            this.initControls();

            return components;
        },

        setContent(html) {
            if (this.disposed) {
                return Promise.resolve();
            }
            if (typeof html !== 'string' || html === '') {
                throw new Error(`Embedded fragment response carries no "${block}" markup`);
            }
            const content = new DOMParser().parseFromString(html, 'text/html').body.childNodes;

            this.manager.dispose();
            this.disposeControls();
            this.containerEl.replaceChildren(...content);
            this.manager = new ComponentManager($(this.containerEl));

            return this.start();
        },

        async reload() {
            if (this.disposed) {
                return;
            }

            mediator.trigger('page:beforeChange');
            try {
                const response = await $.ajax({
                    url: this.endpoint,
                    type: 'GET',
                    dataType: 'json',
                    headers: {[HASH_NAVIGATION_HEADER]: true}
                });

                await this.setContent(response?.[block]);
                showFlashMessages(response?.flashMessages);
            } finally {
                mediator.trigger('page:afterChange');
            }
        },

        dispose() {
            this.disposed = true;
            this.manager.dispose();
            this.disposeControls();
            interactions.dispose();
            this.loadingMask.dispose();
            interactionTracking.abort();
            overlayRoot.remove();
            clearActiveFragment(this);
            showMessagesIn(getActiveFragment()?.messagesContainer);
        }
    };

    const interactions = listenToFragmentInteractions(fragment);
    markActiveWhileInteracted(fragment, interactionTracking.signal);

    return fragment;
}

function blockEndpoint(block, params) {
    return `${EMBEDDED_FRAGMENT_ENDPOINT}/${block}${params ? `?${params}` : ''}`;
}

function markAsEmbeddedRoot(element) {
    element.setAttribute('data-layout', 'separate');
    element.setAttribute(EMBEDDED_MARKER_ATTRIBUTE, '');
}

function createOverlayRoot() {
    const overlayRoot = document.createElement('div');
    overlayRoot.className = OVERLAY_ROOT_CLASS;
    markAsEmbeddedRoot(overlayRoot);
    document.body.appendChild(overlayRoot);

    return overlayRoot;
}

function listenToFragmentInteractions(fragment) {
    const views = [fragment.containerEl, fragment.overlayRoot]
        .map(el => new EmbeddedFragmentView({el, fragment}));

    return {
        dispose: () => views.forEach(view => view.dispose())
    };
}

function markActiveWhileInteracted(fragment, signal) {
    const markActive = () => setActiveFragment(fragment);
    markActive();

    for (const element of [fragment.containerEl, fragment.overlayRoot]) {
        for (const eventName of INTERACTION_EVENTS) {
            element.addEventListener(eventName, markActive, {signal, capture: true});
        }
    }
}
