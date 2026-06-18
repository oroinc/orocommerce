/**
 * Initialize the text trait value from the first textnode child.
 * Shared by variants that display a "text" trait.
 *
 * @param {Object} model GrapesJS component model
 */
export function syncTextTrait(model) {
    const traitText = model.getTrait('text');
    const [textnode] = model.findType('textnode');

    if (traitText && textnode) {
        traitText.setValue(textnode.get('content'));
    }
}

/**
 * @param {Object} model GrapesJS component model
 * @returns {boolean}
 */
export function hasSimpleTextContent(model) {
    const labels = model.get('components').filter(child => !child.is('icon'));

    return labels.length <= 1 && labels.every(child => child.is('textnode'));
}
