<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class CheckStatusOrderResponse
{
    /**
     * @var ResponseCheckStatus
     */
    protected $RESPONSE;

    public function __construct()
    {
    }

    /**
     * @return ResponseCheckStatus
     */
    public function getResponse()
    {
        return $this->RESPONSE;
    }

    /**
     * @param ResponseCheckStatus $RESPONSE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CheckStatusOrderResponse
     */
    public function setResponse($RESPONSE)
    {
        $this->RESPONSE = $RESPONSE;

        return $this;
    }
}
