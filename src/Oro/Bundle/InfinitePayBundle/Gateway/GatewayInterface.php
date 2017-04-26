<?php

namespace Oro\Bundle\InfinitePayBundle\Gateway;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay as SOAP;

interface GatewayInterface
{
    /**
     * @param SOAP\ReserveOrder $reservation
     *
     * @param InfinitePayConfigInterface $config
     * @return SOAP\ReserveOrderResponse
     */
    public function reserve(SOAP\ReserveOrder $reservation, InfinitePayConfigInterface $config);

    /**
     * @param SOAP\CaptureOrder $capture
     *
     * @param InfinitePayConfigInterface $config
     * @return SOAP\CaptureOrderResponse
     */
    public function capture(SOAP\CaptureOrder $capture, InfinitePayConfigInterface $config);

    /**
     * @param SOAP\ActivateOrder $activateOrderRequest
     *
     * @param InfinitePayConfigInterface $config
     * @return SOAP\ActivateOrderResponse
     */
    public function activate(SOAP\ActivateOrder $activateOrderRequest, InfinitePayConfigInterface $config);

    /**
     * @param SOAP\ApplyTransaction $applyTransactionRequest
     *
     * @param InfinitePayConfigInterface $config
     * @return SOAP\ApplyTransactionResponse
     */
    public function applyTransaction(
        SOAP\ApplyTransaction $applyTransactionRequest,
        InfinitePayConfigInterface $config
    );
}
