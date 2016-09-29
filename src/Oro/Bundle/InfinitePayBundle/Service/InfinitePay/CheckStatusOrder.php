<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class CheckStatusOrder
{
    /**
     * @var RequestCheckStatus
     */
    protected $REQUEST;

    /**
     * @param RequestCheckStatus $REQUEST
     */
    public function __construct($REQUEST = null)
    {
        $this->REQUEST = $REQUEST;
    }

    /**
     * @return RequestCheckStatus
     */
    public function getRequest()
    {
        return $this->REQUEST;
    }

    /**
     * @param RequestCheckStatus $REQUEST
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CheckStatusOrder
     */
    public function setRequest($REQUEST)
    {
        $this->REQUEST = $REQUEST;

        return $this;
    }
}
