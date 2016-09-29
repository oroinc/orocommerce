<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ReserveOrder
{
    /**
     * @var RequestReservation
     */
    protected $REQUEST;

    /**
     * @param RequestReservation $REQUEST
     */
    public function __construct($REQUEST = null)
    {
        $this->REQUEST = $REQUEST;
    }

    /**
     * @return RequestReservation
     */
    public function getRequest()
    {
        return $this->REQUEST;
    }

    /**
     * @param RequestReservation $REQUEST
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder
     */
    public function setRequest($REQUEST)
    {
        $this->REQUEST = $REQUEST;

        return $this;
    }
}
