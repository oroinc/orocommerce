import __ from 'orotranslation/js/translator';

/**
 * Placeholder texts considered "not user-entered" — they may be replaced by
 * the selected file name. Anything else is treated as text the user typed.
 */
const DEFAULT_TEXTS = [
    'Link',
    __('oro.cms.wysiwyg.component.link.content'),
    __('oro.cms.wysiwyg.component.link_button.content')
];

/**
 * Extract a human-readable file name from a URL path.
 * @param {string} url
 * @returns {string}
 */
function extractFileName(url) {
    if (!url) {
        return '';
    }

    try {
        const path = new URL(url, window.location.origin).pathname;

        return decodeURIComponent(path.split('/').pop() || '');
    } catch (e) {
        return '';
    }
}

/**
 * Apply a chosen digital-asset file to a link component model: set the href,
 * mark the link with `no-hash`, and (only when the user has not customized them)
 * fill the visible text and title from the file.
 *
 * @param {Object} model GrapesJS link component model
 * @param {Object} metadata File metadata from digital assets
 * @param {string} metadata.url File URL
 * @param {string} [metadata.title] File title
 * @param {string} [metadata.target] Link target
 */
export function applyFileMetadata(model, metadata) {
    const {url, title = '', target} = metadata;
    const hasNonTextComponents = model.get('components')
        .models.some(c => c.get('type') !== 'textnode');

    // Mark the link with `no-hash` so the JS navigation does not intercept the file download click.
    model.addClass('no-hash');

    if (hasNonTextComponents) {
        model.addAttributes({href: url});
        return;
    }

    const [textnode] = model.findType('textnode');
    const currentText = (textnode ? textnode.get('content') : '').trim();
    const currentTitle = (model.getAttributes().title || '').trim();

    const isAutoText = !currentText ||
        DEFAULT_TEXTS.includes(currentText) ||
        currentText === (model._fileText || '');
    const isAutoTitle = !currentTitle || currentTitle === (model._fileTitle || '');

    const newAttrs = {href: url};

    if (title && isAutoTitle) {
        newAttrs.title = title;
        model._fileTitle = title;
    }

    if (target) {
        newAttrs.target = target;
    }

    model.addAttributes(newAttrs);

    if (!isAutoText) {
        return;
    }

    const displayText = title || extractFileName(url);

    if (!displayText) {
        return;
    }

    model._fileText = displayText;

    const traitText = model.getTrait('text');

    if (traitText) {
        traitText.setValue(displayText);
    } else if (!model.get('components').length) {
        model.components([{type: 'textnode', content: displayText}]);
    } else if (textnode) {
        textnode.set('content', displayText);
    }
}
