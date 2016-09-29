<?php

namespace Oro\Bundle\InfinitePayBundle\Gateway;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay as SOAP;

interface GatewayInterface
{
    /**
     * @param SOAP\ReserveOrder $reservation
     *
     * @return SOAP\ReserveOrderResponse
     */
    public function reserve(SOAP\ReserveOrder $reservation);

    /**
     * @param SOAP\CaptureOrder $capture
     *
     * @return SOAP\CaptureOrderResponse
     */
    public function capture(SOAP\CaptureOrder $capture);

    /**
     * @param SOAP\ActivateOrder $activateOrderRequest
     *
     * @return SOAP\ActivateOrderResponse
     */
    public function activate(SOAP\ActivateOrder $activateOrderRequest);

    /**
     * @param SOAP\ApplyTransaction $applyTransactionRequest
     *
     * @return SOAP\ApplyTransactionResponse
     */
    public function applyTransaction(SOAP\ApplyTransaction $applyTransactionRequest);
}
