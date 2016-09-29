<?php

namespace Oro\Bundle\InfinitePayBundle\Gateway;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay as SOAP;

/**
 * @codeCoverageIgnore
 */
class SoapGateway implements GatewayInterface
{
    /**
     * @var SOAP\ApiWrapper
     */
    protected $clientWrapper;

    /**
     * @param SOAP\ApiWrapper $apiWrapper
     */
    public function __construct(SOAP\ApiWrapper $apiWrapper)
    {
        $this->clientWrapper = $apiWrapper;
    }

    /**
     * @param SOAP\ReserveOrder $reservation
     *
     * @return SOAP\ReserveOrderResponse
     */
    public function reserve(SOAP\ReserveOrder $reservation)
    {
        return $this->clientWrapper->getClient()->reserveOrder($reservation);
    }

    /**
     * @param SOAP\CaptureOrder $capture
     *
     * @return SOAP\CaptureOrderResponse
     */
    public function capture(SOAP\CaptureOrder $capture)
    {
        return $this->clientWrapper->getClient()->callCaptureOrder($capture);
    }

    /**
     * @param SOAP\ActivateOrder $activateOrder
     *
     * @return SOAP\ActivateOrderResponse
     */
    public function activate(SOAP\ActivateOrder $activateOrder)
    {
        return $this->clientWrapper->getClient()->activateOrder($activateOrder);
    }

    /**
     * @param SOAP\ApplyTransaction $applyTransactionRequest
     *
     * @return SOAP\ApplyTransactionResponse
     */
    public function applyTransaction(SOAP\ApplyTransaction $applyTransactionRequest)
    {
        return $this->clientWrapper->getClient()->applyTransactionOnActivatedOrder($applyTransactionRequest);
    }
}
