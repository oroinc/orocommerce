<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ActivateOrder
{
    /**
     * @var RequestActivation
     */
    protected $REQUEST;

    /**
     * @param RequestActivation $REQUEST
     */
    public function __construct($REQUEST = null)
    {
        $this->REQUEST = $REQUEST;
    }

    /**
     * @return RequestActivation
     */
    public function getRequest()
    {
        return $this->REQUEST;
    }

    /**
     * @param RequestActivation $REQUEST
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ActivateOrder
     */
    public function setRequest($REQUEST)
    {
        $this->REQUEST = $REQUEST;

        return $this;
    }
}
