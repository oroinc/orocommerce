import __ from 'orotranslation/js/translator';
import CONSTANTS from 'orocms/js/app/grapesjs/constants';

const regexp = new RegExp(`\\bid=[\\"'].*${CONSTANTS.ISOLATION_PREFIX}`, 'g');

/**
 * @param {object} parameters
 * @returns {{errorMessage}|undefined}
 */
export default function findReservedIdValues(parameters) {
    const {cache, htmlStringLine} = parameters;

    if (!cache[htmlStringLine]) {
        cache[htmlStringLine] = htmlStringLine.match(regexp);
    }

    if (cache[htmlStringLine]) {
        return __('oro.htmlpurifier.messages.reserved_id', {id: CONSTANTS.ISOLATION_PREFIX});
    }
}
