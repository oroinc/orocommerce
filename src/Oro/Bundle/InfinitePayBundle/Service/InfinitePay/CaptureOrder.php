<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class CaptureOrder
{
    /**
     * @var RequestCapture
     */
    protected $REQUEST;

    /**
     * @param RequestCapture $REQUEST
     */
    public function __construct($REQUEST = null)
    {
        $this->REQUEST = $REQUEST;
    }

    /**
     * @return RequestCapture
     */
    public function getRequest()
    {
        return $this->REQUEST;
    }

    /**
     * @param RequestCapture $REQUEST
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CaptureOrder
     */
    public function setRequest($REQUEST)
    {
        $this->REQUEST = $REQUEST;

        return $this;
    }
}
