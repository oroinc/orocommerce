import {macros} from 'underscore';
import quantityHelper from 'oroproduct/js/app/quantity-helper';
import routing from 'routing';

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
     * @param {string?} data.popover_image_src
     * @param {array} data.popover_image_sources
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
     * Renders inventory status as tooltip for product item
     *
     * @param {string} name
     * @param {string} label
     */
    renderInventoryTooltipStatus:
        require('tpl-loader!oroshoppinglist/templates/macros/product-item__inventory_tooltip_status.html'),

    /**
     * Renders notes for product item
     *
     * @param {string} notes
     * @param {number?} [clipLength = 30]
     */
    renderNotes: require('tpl-loader!oroshoppinglist/templates/macros/product-item__notes.html'),

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
     * Renders warnings for product item
     *
     * @param {array} warnings
     */
    renderWarnings: require('tpl-loader!oroshoppinglist/templates/macros/product-item__warnings.html'),

    /**
     * Renders button to show more hidden product variants
     *
     * @param {Object} data
     * @param {array} data.elements
     * @param {string} data.groupName
     * @param {string?} data.hideClass
     */
    renderMoreVariantsButton: require('tpl-loader!oroshoppinglist/templates/macros/product-item__variants-btn.html'),

    /**
     * Include quantityHelper to templates
     */
    quantityHelper,

    /**
     * Include routing to templates
     */
    routing,

    /**
     * Renders formatted unit
     *
     * @param {Object} data
     * @param {string} unit
     * @param {number} quantity
     */
    renderUnit: require('tpl-loader!oroshoppinglist/templates/macros/product-item__unit.html'),

    /**
     * Renders button to expand or collapse product kit items
     *
     * @param {Object} data
     * @param {string} data.productId
     * @param {string} data.btnClass
     * @param {string} data.productName
     * @param {boolean} data.showLabel
     */
    renderExpandKitsButton: require('tpl-loader!oroshoppinglist/templates/macros/product-kit__expand-btn.html'),

    /**
     * Render line item quantity and unit input
     *
     * @param {Object} obj
     * @param {number} quantity
     * @param {number} precision
     * @param {Array} units
     * @param {string} unitCode
     * @param {Object} gridThemeOptions
     */
    renderLineItemQuantityUnitInput:
        require('tpl-loader!oroshoppinglist/templates/macros/line-item-quantity-unit__input.html')
});
