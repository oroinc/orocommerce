<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class RequestCheckStatus extends GenericRequest
{
    /**
     * @var ClientData
     */
    protected $CLIENT_DATA;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestCheckStatus
     */
    public function setClientData($CLIENT_DATA)
    {
        $this->CLIENT_DATA = $CLIENT_DATA;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestCheckStatus
     */
    public function setOrderData($ORDER_DATA)
    {
        $this->ORDER_DATA = $ORDER_DATA;

        return $this;
    }
}
