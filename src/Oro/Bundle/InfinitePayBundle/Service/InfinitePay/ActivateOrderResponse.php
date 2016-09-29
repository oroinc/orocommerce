<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ActivateOrderResponse implements ResponseBodyInterface
{
    /**
     * @var ResponseActivation
     */
    protected $RESPONSE;

    /**
     * @return ResponseActivation
     */
    public function getResponse()
    {
        return $this->RESPONSE;
    }

    /**
     * @param ResponseActivation $RESPONSE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ActivateOrderResponse
     */
    public function setResponse($RESPONSE)
    {
        $this->RESPONSE = $RESPONSE;

        return $this;
    }
}
