<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Builder\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;

class ApruveInvoiceBuilder implements ApruveInvoiceBuilderInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var int
     */
    private $amountCents;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var array
     */
    private $lineItems;

    /**
     * @param int    $amountCents
     * @param string $currency
     * @param array  $lineItems
     */
    public function __construct($amountCents, $currency, array $lineItems)
    {
        $this->amountCents = $amountCents;
        $this->currency = $currency;
        $this->lineItems = $lineItems;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $this->data += [
            ApruveInvoice::AMOUNT_CENTS => (int)$this->amountCents,
            ApruveInvoice::CURRENCY => (string)$this->currency,
            ApruveInvoice::LINE_ITEMS => (array)$this->lineItems,
        ];

        return new ApruveInvoice($this->data);
    }

    /**
     * {@inheritDoc}
     */
    public function setIssueOnCreate($bool)
    {
        $this->data[ApruveInvoice::ISSUE_ON_CREATE] = (bool)$bool;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setDueAt($dueAt)
    {
        $this->data[ApruveInvoice::DUE_AT] = (string)$dueAt;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantInvoiceId($invoiceId)
    {
        $this->data[ApruveInvoice::MERCHANT_INVOICE_ID] = (string)$invoiceId;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingCents($amount)
    {
        $this->data[ApruveInvoice::SHIPPING_CENTS] = (int)$amount;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTaxCents($amount)
    {
        $this->data[ApruveInvoice::TAX_CENTS] = (int)$amount;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setMerchantNotes($notes)
    {
        $this->data[ApruveInvoice::MERCHANT_NOTES] = (string)$notes;

        return $this;
    }
}
