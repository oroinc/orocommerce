<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

class ApruveOrder extends AbstractApruveEntity
{
    /**
     * Mandatory
     */
    const MERCHANT_ID = 'merchant_id';
    const AMOUNT_CENTS = 'amount_cents';
    const CURRENCY = 'currency';
    const LINE_ITEMS = 'order_items';

    /**
     * Optional
     */
    const MERCHANT_ORDER_ID = 'merchant_order_id';
    const TAX_CENTS = 'tax_cents';
    const SHIPPING_CENTS = 'shipping_cents';
    const EXPIRE_AT = 'expire_at';
    const AUTO_ESCALATE = 'auto_escalate';
    const PO_NUMBER = 'po_number';
    const PAYMENT_TERM_PARAMS = 'payment_term_params';
    const _CORPORATE_ACCOUNT_ID = 'corporate_account_id';
    const FINALIZE_ON_CREATE = 'finalize_on_create';
    const INVOICE_ON_CREATE = 'invoice_on_create';

    /**
     * Required for offline (created manually via Apruve API) orders only.
     */
    const SHOPPER_ID = 'shopper_id';
}
