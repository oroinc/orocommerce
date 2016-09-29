<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class CancelOrder
{
    /**
     * @var RequestCancel
     */
    protected $REQUEST;

    /**
     * @param RequestCancel $REQUEST
     */
    public function __construct($REQUEST = null)
    {
        $this->REQUEST = $REQUEST;
    }

    /**
     * @return RequestCancel
     */
    public function getRequest()
    {
        return $this->REQUEST;
    }

    /**
     * @param RequestCancel $REQUEST
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CancelOrder
     */
    public function setRequest($REQUEST)
    {
        $this->REQUEST = $REQUEST;

        return $this;
    }
}
