import messenger from 'oroui/js/messenger';
import {MESSAGES_CONTAINER_CLASS} from './config';

export function createMessagesContainer(overlayRoot) {
    const el = document.createElement('div');
    el.className = MESSAGES_CONTAINER_CLASS;
    overlayRoot.appendChild(el);

    showMessagesIn(el);

    return el;
}

export function showMessagesIn(container) {
    messenger.setup({container: container ?? ''});

    if (container) {
        messenger.flushStoredMessages();
    }
}

export function showFlashMessages(messagesByType) {
    for (const [type, messages] of Object.entries(messagesByType ?? {})) {
        for (const message of messages) {
            messenger.notificationFlashMessage(type, message);
        }
    }
}
