import {macros} from 'underscore';

macros('oroshoppinglist', {
    /**
     * Renders image with gallery popup, or image placeholder
     *
     * @param {Object} data
     * @param {string?} data.src
     * @param {string?} data.srcPlaceholder
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
    renderProductItemName: require('tpl-loader!oroshoppinglist/templates/macros/product-item__name.html')
});
