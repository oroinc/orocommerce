import __ from 'orotranslation/js/translator';
const REGEXP_ID_VALUE = /\sid=\"([^"]*?)(?=\")/g;

function getAllDuplicateIds(document) {
    const elements = [...document.querySelectorAll('[id]')];
    const occurrences = new Map();

    elements.forEach(({id}) => occurrences.set(id, (occurrences.get(id) ?? 0) + 1));

    return elements.filter(({id}) => occurrences.get(id) > 1);
}

/**
 * Find "id" attributes collision
 *
 * @param {object} parameters
 *
 * @returns {{errorMessage}|undefined}
 *
 */

function idCollision(parameters) {
    const {cache, htmlStringLine, htmlFragment} = parameters;

    if (cache.duplicateIds === void 0) {
        cache.duplicateIds = getAllDuplicateIds(htmlFragment);
    }

    const ids = cache.duplicateIds;
    let matches = htmlStringLine.match(REGEXP_ID_VALUE);

    if (cache.collectIds === void 0) {
        cache.collectIds = [];
    }

    if (!ids.length || matches === null) {
        return;
    }

    matches = matches.map(match => match.replace(/\sid=\"/g, ''));

    let errorMessage;

    for (const value of matches) {
        const found = ids.find(({id}) => {
            return id === value.trim();
        });

        if (found) {
            if (cache.collectIds.includes(found.id)) {
                errorMessage = __('oro.htmlpurifier.messages.AttrValidator: Attribute removed', {
                    name: 'id',
                    value: found.id,
                    tagName: `<${found.tagName}>`
                });
            }

            cache.collectIds.push(found.id);
        }
    }

    return errorMessage;
}

export default idCollision;
