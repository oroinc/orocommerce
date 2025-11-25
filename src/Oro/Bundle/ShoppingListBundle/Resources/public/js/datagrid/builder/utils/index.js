import {uniqueId, uniq} from 'underscore';

/**
 *
 * @param item
 * @param classNames
 */
export const addClass = (item, classNames = []) => {
    if (item.row_class_name === void 0) {
        item.row_class_name = '';
    }

    const classes = item.row_class_name.split(' ');

    item.row_class_name = uniq(classes.concat(classNames)).join(' ');
};

/**
 *
 * @param item
 * @param classNames
 */
export const removeClass = (item, classNames = []) => {
    if (item.row_class_name === void 0) {
        return;
    }

    item.row_class_name = item.row_class_name.split(' ')
        .filter(className => !classNames.split(' ').includes(className)).join(' ');
};

export const isUpcoming = item => item.isUpcoming;
export const isHighlight = item => item.warnings && item.warnings.length > 0;

export const isError = item => item.errors && item.errors.length > 0;

export const messageModel = (item, columnName, opts = {}) => {
    const messageRowId = `${item.productUID}m`;
    const messageItem = {
        ...item,
        id: item.id + uniqueId('-bind-'),
        renderColumnName: columnName,
        row_class_name: item.row_class_name + ' extension-row notification-row',
        _templateKey: 'message',
        isMessage: true,
        isAuxiliary: true,
        rowId: messageRowId,
        row_attributes: {
            ...item.row_attributes,
            'aria-hidden': true,
            'data-row-id': messageRowId
        },
        ...opts
    };

    item.row_class_name += ' has-message-row';
    item.row_attributes = {
        ...item.row_attributes,
        'data-related-row': messageItem.rowId
    };
    item.messageModelId = messageItem.id;
    return messageItem;
};
