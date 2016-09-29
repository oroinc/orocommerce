<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class RequestCapture extends GenericRequest
{
    /**
     * @var ClientData
     */
    protected $CLIENT_DATA;

    /**
     * @var DebtorData
     */
    protected $DEBTOR_DATA;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestCapture
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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestCapture
     */
    public function setDebtorData($DEBTOR_DATA)
    {
        $this->DEBTOR_DATA = $DEBTOR_DATA;

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
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\RequestCapture
     */
    public function setOrderData($ORDER_DATA)
    {
        $this->ORDER_DATA = $ORDER_DATA;

        return $this;
    }
}
