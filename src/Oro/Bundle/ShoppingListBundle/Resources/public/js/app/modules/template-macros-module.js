import _ from 'underscore';
import quantityHelper from 'oroproduct/js/app/quantity-helper';
import routing from 'routing';
import renderProductItemImage from 'tpl-loader!oroshoppinglist/templates/macros/product-item__image.html';
import renderProductItemName from 'tpl-loader!oroshoppinglist/templates/macros/product-item__name.html';
import renderInventoryStatus from 'tpl-loader!oroshoppinglist/templates/macros/product-item__inventory_status.html';
import renderInventoryTooltipStatus
    from 'tpl-loader!oroshoppinglist/templates/macros/product-item__inventory_tooltip_status.html';
import renderNotes from 'tpl-loader!oroshoppinglist/templates/macros/product-item__notes.html';
import renderUpcoming from 'tpl-loader!oroshoppinglist/templates/macros/product-item__upcoming.html';
import renderErrors from 'tpl-loader!oroshoppinglist/templates/macros/product-item__errors.html';
import renderWarnings from 'tpl-loader!oroshoppinglist/templates/macros/product-item__warnings.html';
import renderMoreVariantsButton from 'tpl-loader!oroshoppinglist/templates/macros/product-item__variants-btn.html';
import renderUnit from 'tpl-loader!oroshoppinglist/templates/macros/product-item__unit.html';
import renderExpandKitsButton from 'tpl-loader!oroshoppinglist/templates/macros/product-kit__expand-btn.html';

_.macros('oroshoppinglist', {
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
    renderProductItemImage,

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
    renderProductItemName,

    /**
     * Renders inventory status block for product item
     *
     * @param {string} name
     * @param {string} label
     */
    renderInventoryStatus,

    /**
     * Renders inventory status as tooltip for product item
     *
     * @param {string} name
     * @param {string} label
     */
    renderInventoryTooltipStatus,

    /**
     * Renders notes for product item
     *
     * @param {string} notes
     * @param {number?} [clipLength = 30]
     */
    renderNotes,

    /**
     * Renders upcoming warning for product item
     *
     * @param {boolean} isUpcoming
     * @param {string?} availabilityDate
     */
    renderUpcoming,

    /**
     * Renders errors for product item
     *
     * @param {array} errors
     */
    renderErrors,

    /**
     * Renders warnings for product item
     *
     * @param {array} errors
     */
    renderWarnings,

    /**
     * Renders button to show more hidden product variants
     *
     * @param {Object} data
     * @param {array} data.elements
     * @param {string} data.groupName
     * @param {string?} data.hideClass
     */
    renderMoreVariantsButton,

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
    renderUnit,

    /**
     * Renders button to expand or collapse product kit items
     *
     * @param {Object} data
     * @param {string} data.productId
     * @param {string} data.btnClass
     * @param {string} data.productName
     * @param {boolean} data.showLabel
     */
    renderExpandKitsButton
});
