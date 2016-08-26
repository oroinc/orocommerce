/*global define*/
define([
    'jquery',
    'underscore',
    'orotranslation/js/translator',
    'oro/filter/product-price-filter'
], function($, _, __, ProductPriceFilter) {
    'use strict';

    var FrontendProductPriceFilter;

    /**
     * Frontend product price filter
     *
     * @export  oro/filter/product-price-filter
     * @class   oro.filter.FrontendProductPriceFilter
     * @extends oro.filter.ProductPriceFilter
     */
    FrontendProductPriceFilter = ProductPriceFilter.extend({});

    return FrontendProductPriceFilter;
});
