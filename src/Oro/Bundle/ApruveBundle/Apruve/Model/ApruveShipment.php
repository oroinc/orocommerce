<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

class ApruveShipment extends AbstractApruveEntity
{
    /**
     * Mandatory
     */
    const AMOUNT_CENTS = 'amount_cents';
    const CURRENCY = 'currency';
    const LINE_ITEMS = 'shipment_items';

    /**
     * Optional
     */
    const TAX_CENTS = 'tax_cents';
    const SHIPPING_CENTS = 'shipping_cents';
    const SHIPPER = 'shipper';
    const TRACKING_NUMBER = 'tracking_number';
    const SHIPPED_AT = 'shipped_at';
    const DELIVERED_AT = 'delivered_at';
    const STATUS = 'status';
    const MERCHANT_SHIPMENT_ID = 'merchant_shipment_id';
    const MERCHANT_NOTES = 'merchant_notes';
}
