<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class RequestActivation extends GenericRequest
{
    /**
     * @var ClientData
     */
    protected $CLIENT_DATA;

    /**
     * @var InvoiceData
     */
    protected $INVOICE_DATA;

    /**
     * @var OrderTotal
     */
    protected $ORDER_DATA;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestActivation
     */
    public function setClientData($CLIENT_DATA)
    {
        $this->CLIENT_DATA = $CLIENT_DATA;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestActivation
     */
    public function setInvoiceData($INVOICE_DATA)
    {
        $this->INVOICE_DATA = $INVOICE_DATA;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestActivation
     */
    public function setOrderData($ORDER_DATA)
    {
        $this->ORDER_DATA = $ORDER_DATA;

        return $this;
    }
}
