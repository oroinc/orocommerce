import {macros} from 'underscore';

macros('oroshoppinglist', {
    /**
     * Renders image with gallery popup, or image placeholder
     *
     * @param {Object} data
     * @param {string?} data.src
     * @param {string?} data.placeholder
     * @param {string} data.productId
     * @param {string} data.title
     * @param {string} data.alt
     */
    renderProductItemImage: require('tpl-loader!oroshoppinglist/templates/macros/product-item__image.html'),

    /**
     * Renders title block for product item
     *
     * @param {Object} data
     * @param {boolean?} data.clip
     * @param {string} data.name
     * @param {string} data.link
     */
    renderProductItemName: require('tpl-loader!oroshoppinglist/templates/macros/product-item__name.html'),

    /**
     * Renders inventory status block for product item
     *
     * @param {string} name
     * @param {string} label
     */
    renderInventoryStatus: require('tpl-loader!oroshoppinglist/templates/macros/product-item__inventory_status.html'),

    /**
     * Renders note for product item
     *
     * @param {string} note
     * @param {number?} [clipLength = 30]
     */
    renderNote: require('tpl-loader!oroshoppinglist/templates/macros/product-item__note.html'),

    /**
     * Renders upcoming warning for product item
     *
     * @param {boolean} isUpcoming
     * @param {string?} availabilityDate
     */
    renderUpcoming: require('tpl-loader!oroshoppinglist/templates/macros/product-item__upcoming.html'),

    /**
     * Renders errors for product item
     *
     * @param {array} errors
     */
    renderErrors: require('tpl-loader!oroshoppinglist/templates/macros/product-item__errors.html'),

    /**
     * Renders button to show more hidden product variants
     *
     * @param {Object} data
     * @param {array} data.elements
     * @param {string} data.groupName
     * @param {string?} data.hideClass
     */
    renderMoreVariantsButton: require('tpl-loader!oroshoppinglist/templates/macros/product-item__variants-btn.html')
});
