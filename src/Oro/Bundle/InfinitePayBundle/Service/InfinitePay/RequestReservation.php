<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class RequestReservation extends GenericRequest
{
    /**
     * @var BankData
     */
    protected $BANK_DATA;

    /**
     * @var ClientData
     */
    protected $CLIENT_DATA;

    /**
     * @var DebtorData
     */
    protected $DEBTOR_DATA;

    /**
     * @var InvoiceData
     */
    protected $INVOICE_DATA;

    /**
     * @var OrderArticleList
     */
    protected $ARTICLES;

    /**
     * @var OrderTotal
     */
    protected $ORDER_DATA;

    /**
     * @var ShippingData
     */
    protected $SHIPPING_DATA;

    /**
     * @return BankData
     */
    public function getBankData()
    {
        return $this->BANK_DATA;
    }

    /**
     * @param BankData $BANK_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation
     */
    public function setBankData($BANK_DATA)
    {
        $this->BANK_DATA = $BANK_DATA;

        return $this;
    }

    /**
     * @return ClientData
     */
    public function getClientData()
    {
        return $this->CLIENT_DATA;
    }

    /**
     * @param ClientData $CLIENT_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation
     */
    public function setClientData($CLIENT_DATA)
    {
        $this->CLIENT_DATA = $CLIENT_DATA;

        return $this;
    }

    /**
     * @return DebtorData
     */
    public function getDebtorData()
    {
        return $this->DEBTOR_DATA;
    }

    /**
     * @param DebtorData $DEBTOR_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation
     */
    public function setDebtorData($DEBTOR_DATA)
    {
        $this->DEBTOR_DATA = $DEBTOR_DATA;

        return $this;
    }

    /**
     * @return InvoiceData
     */
    public function getInvoiceData()
    {
        return $this->INVOICE_DATA;
    }

    /**
     * @param InvoiceData $INVOICE_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation
     */
    public function setInvoiceData($INVOICE_DATA)
    {
        $this->INVOICE_DATA = $INVOICE_DATA;

        return $this;
    }

    /**
     * @return OrderArticleList
     */
    public function getArticles()
    {
        return $this->ARTICLES;
    }

    /**
     * @param OrderArticleList $ARTICLES
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation
     */
    public function setArticles($ARTICLES)
    {
        $this->ARTICLES = $ARTICLES;

        return $this;
    }

    /**
     * @return OrderTotal
     */
    public function getOrderData()
    {
        return $this->ORDER_DATA;
    }

    /**
     * @param OrderTotal $ORDER_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation
     */
    public function setOrderData($ORDER_DATA)
    {
        $this->ORDER_DATA = $ORDER_DATA;

        return $this;
    }

    /**
     * @return ShippingData
     */
    public function getShippingData()
    {
        return $this->SHIPPING_DATA;
    }

    /**
     * @param ShippingData $SHIPPING_DATA
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestReservation
     */
    public function setShippingData($SHIPPING_DATA)
    {
        $this->SHIPPING_DATA = $SHIPPING_DATA;

        return $this;
    }
}
