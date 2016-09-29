<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ApplyTransactionResponse implements ResponseBodyInterface
{
    /**
     * @var ResponseApplyTransaction
     */
    protected $RESPONSE;

    /**
     * @return ResponseApplyTransaction
     */
    public function getResponse()
    {
        return $this->RESPONSE;
    }

    /**
     * @param ResponseApplyTransaction $RESPONSE
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ApplyTransactionResponse
     */
    public function setResponse($RESPONSE)
    {
        $this->RESPONSE = $RESPONSE;

        return $this;
    }
}
