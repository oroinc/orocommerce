<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class InvoiceData
{
    /**
     * @var string
     */
    protected $COMMENT;

    /**
     * @var string
     */
    protected $DEBTOR_ACCOUNT;

    /**
     * @var string
     */
    protected $DELAY_IN_DAYS;

    /**
     * @var string
     */
    protected $DELIVERY_DATE;

    /**
     * @var string
     */
    protected $DUE_DATE;

    /**
     * @var string
     */
    protected $INVOICE_DATE;

    /**
     * @var string
     */
    protected $INVOICE_ID;

    /**
     * @var string
     */
    protected $PAYMENT_TERMS;

    /**
     * @param string $COMMENT
     * @param string $DEBTOR_ACCOUNT
     * @param string $DELAY_IN_DAYS
     * @param string $DELIVERY_DATE
     * @param string $DUE_DATE
     * @param string $INVOICE_DATE
     * @param string $INVOICE_ID
     * @param string $PAYMENT_TERMS
     */
    public function __construct(
        $COMMENT = null,
        $DEBTOR_ACCOUNT = null,
        $DELAY_IN_DAYS = null,
        $DELIVERY_DATE = null,
        $DUE_DATE = null,
        $INVOICE_DATE = null,
        $INVOICE_ID = null,
        $PAYMENT_TERMS = null
    ) {
        $this->COMMENT = $COMMENT;
        $this->DEBTOR_ACCOUNT = $DEBTOR_ACCOUNT;
        $this->DELAY_IN_DAYS = $DELAY_IN_DAYS;
        $this->DELIVERY_DATE = $DELIVERY_DATE;
        $this->DUE_DATE = $DUE_DATE;
        $this->INVOICE_DATE = $INVOICE_DATE;
        $this->INVOICE_ID = $INVOICE_ID;
        $this->PAYMENT_TERMS = $PAYMENT_TERMS;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->COMMENT;
    }

    /**
     * @param string $COMMENT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setComment($COMMENT)
    {
        $this->COMMENT = $COMMENT;

        return $this;
    }

    /**
     * @return string
     */
    public function getDebtorAccount()
    {
        return $this->DEBTOR_ACCOUNT;
    }

    /**
     * @param string $DEBTOR_ACCOUNT
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setDebtorAccount($DEBTOR_ACCOUNT)
    {
        $this->DEBTOR_ACCOUNT = $DEBTOR_ACCOUNT;

        return $this;
    }

    /**
     * @return string
     */
    public function getDelayInDays()
    {
        return $this->DELAY_IN_DAYS;
    }

    /**
     * @param string $DELAY_IN_DAYS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setDelayInDays($DELAY_IN_DAYS)
    {
        $this->DELAY_IN_DAYS = $DELAY_IN_DAYS;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryDate()
    {
        return $this->DELIVERY_DATE;
    }

    /**
     * @param string $DELIVERY_DATE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setDeliveryDate($DELIVERY_DATE)
    {
        $this->DELIVERY_DATE = $DELIVERY_DATE;

        return $this;
    }

    /**
     * @return string
     */
    public function getDueDate()
    {
        return $this->DUE_DATE;
    }

    /**
     * @param string $DUE_DATE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setDueDate($DUE_DATE)
    {
        $this->DUE_DATE = $DUE_DATE;

        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceDate()
    {
        return $this->INVOICE_DATE;
    }

    /**
     * @param string $INVOICE_DATE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setInvoiceDate($INVOICE_DATE)
    {
        $this->INVOICE_DATE = $INVOICE_DATE;

        return $this;
    }

    /**
     * @return string
     */
    public function getInvoiceId()
    {
        return $this->INVOICE_ID;
    }

    /**
     * @param string $INVOICE_ID
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setInvoiceId($INVOICE_ID)
    {
        $this->INVOICE_ID = $INVOICE_ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentTerms()
    {
        return $this->PAYMENT_TERMS;
    }

    /**
     * @param string $PAYMENT_TERMS
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData
     */
    public function setPaymentTerms($PAYMENT_TERMS)
    {
        $this->PAYMENT_TERMS = $PAYMENT_TERMS;

        return $this;
    }
}
