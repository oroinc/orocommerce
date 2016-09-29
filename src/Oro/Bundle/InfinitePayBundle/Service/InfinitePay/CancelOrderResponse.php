<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class CancelOrderResponse
{
    /**
     * @var ResponseCancel
     */
    protected $RESPONSE;

    public function __construct()
    {
    }

    /**
     * @return ResponseCancel
     */
    public function getResponse()
    {
        return $this->RESPONSE;
    }

    /**
     * @param ResponseCancel $RESPONSE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\CancelOrderResponse
     */
    public function setResponse($RESPONSE)
    {
        $this->RESPONSE = $RESPONSE;

        return $this;
    }
}
