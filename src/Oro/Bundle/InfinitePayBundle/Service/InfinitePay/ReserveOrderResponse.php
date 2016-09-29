<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class ReserveOrderResponse implements ResponseBodyInterface
{
    /**
     * @var ResponseReservation
     */
    protected $RESPONSE;

    /**
     * @param ResponseReservation|null $responseReservation
     */
    public function __construct(ResponseReservation $responseReservation = null)
    {
        $this->RESPONSE = $responseReservation;
    }

    /**
     * @return ResponseReservation
     */
    public function getResponse()
    {
        return $this->RESPONSE;
    }

    /**
     * @param ResponseReservation $RESPONSE
     *
     * @return ReserveOrderResponse
     */
    public function setResponse(ResponseReservation $RESPONSE)
    {
        $this->RESPONSE = $RESPONSE;

        return $this;
    }
}
