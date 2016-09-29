<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ModifyReservedOrderResponse
{
    /**
     * @var ResponseModify
     */
    protected $RESPONSE;

    public function __construct()
    {
    }

    /**
     * @return ResponseModify
     */
    public function getResponse()
    {
        return $this->RESPONSE;
    }

    /**
     * @param ResponseModify $RESPONSE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ModifyReservedOrderResponse
     */
    public function setResponse($RESPONSE)
    {
        $this->RESPONSE = $RESPONSE;

        return $this;
    }
}
