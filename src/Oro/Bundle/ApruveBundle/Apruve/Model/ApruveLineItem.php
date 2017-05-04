<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

class ApruveLineItem extends AbstractApruveEntity
{
    /**
     * Mandatory
     */
    const TITLE = 'title';
    /**
     * Property 'price_total_cents' is not respected by Apruve when secure hash is generated,
     * hence we use 'amount_cents' instead.
     * @see README.md, section "Things to Consider"
     */
    const AMOUNT_CENTS = 'amount_cents';
    const PRICE_TOTAL_CENTS = 'price_total_cents';
    const QUANTITY = 'quantity';
    const CURRENCY = 'currency';

    /**
     * Optional
     */
    const SKU = 'sku';
    const DESCRIPTION = 'description';
    const VIEW_PRODUCT_URL = 'view_product_url';
    const PRICE_EA_CENTS = 'price_ea_cents';
    const VENDOR = 'vendor';
    const MERCHANT_NOTES = 'merchant_notes';
    const VARIANT_INFO = 'variant_info';
}
