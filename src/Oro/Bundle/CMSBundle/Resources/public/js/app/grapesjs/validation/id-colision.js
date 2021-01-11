import __ from 'orotranslation/js/translator';
const REGEXP_ID_VALUE = /\sid=\"([^"]*?)(?=\")/g;

/**
 * Find "id" attributes collision
 * @param str
 * @returns {[]}
 */
const idCollision = function(str) {
    const domParser = new DOMParser();
    const createdDocument = domParser.parseFromString(str, 'text/html');

    const ids = getAllDuplicateIds(createdDocument);
    if (!ids.length) {
        return [];
    }

    const lines = str.split('\n');
    const duplicatesIds = [];
    const messages = [];

    lines.forEach((line, index) => {
        const lineNumber = index + 1;
        let matches = line.match(REGEXP_ID_VALUE);

        if (matches) {
            matches = matches.map(match => match.replace(/\sid=\"/g, ''));
            for (const value of matches) {
                const found = ids.find(({id}) => {
                    return id === value.trim();
                });

                if (found) {
                    if (duplicatesIds.indexOf(found.id) !== -1) {
                        messages.push({
                            line: lineNumber,
                            message: __('oro.htmlpurifier.formatted_error_line', {
                                line: lineNumber,
                                message: __('oro.htmlpurifier.messages.AttrValidator: Attribute removed', {
                                    name: 'id',
                                    value: found.id,
                                    tagName: `<${found.tagName}>`
                                })
                            })
                        });
                    }

                    duplicatesIds.push(found.id);
                }
            }
        }
    });

    return messages;
};

function getAllDuplicateIds(document) {
    const elements = [...document.querySelectorAll('[id]')];
    return elements.reduce((duplicates, el, index, collection) => {
        if (collection.find(({id}) => id === el.id)) {
            if (!duplicates.find(({id}) => id === el.id)) {
                duplicates.push({
                    id: el.id.trim(),
                    tagName: el.tagName.toLowerCase()
                });
            }
        }
        return duplicates;
    }, []);
}

export default idCollision;
