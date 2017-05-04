<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model;

class ApruveInvoice extends AbstractApruveEntity
{
    /**
     * Mandatory
     */
    const AMOUNT_CENTS = 'amount_cents';
    const CURRENCY = 'currency';
    const LINE_ITEMS = 'invoice_items';

    /**
     * Optional
     */
    const TAX_CENTS = 'tax_cents';
    const SHIPPING_CENTS = 'shipping_cents';
    const ISSUE_ON_CREATE = 'issue_on_create';
    const DUE_AT = 'due_at';
    const MERCHANT_INVOICE_ID = 'merchant_invoice_id';
    const MERCHANT_NOTES = 'merchant_notes';
}
