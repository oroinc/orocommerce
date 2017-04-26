<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

interface InfinitePayClientInterface
{
    /**
     * @param ReserveOrder $parameters
     *
     * @return reserveOrderResponse
     */
    public function reserveOrder(ReserveOrder $parameters);

    /**
     * @param CaptureOrder $parameters
     *
     * @return CaptureOrderResponse
     */
    public function callCaptureOrder(CaptureOrder $parameters);

    /**
     * @param ActivateOrder $parameters
     *
     * @return ActivateOrderResponse
     */
    public function activateOrder(ActivateOrder $parameters);

    /**
     * @param ApplyTransaction $parameters
     *
     * @return ApplyTransactionResponse
     */
    public function applyTransactionOnActivatedOrder(ApplyTransaction $parameters);

    /**
     * @param CancelOrder $parameters
     *
     * @return CancelOrderResponse
     */
    public function cancelOrder(CancelOrder $parameters);

    /**
     * @param ModifyReservedOrder $parameters
     *
     * @return ModifyReservedOrderResponse
     */
    public function modifyReservedOrder(ModifyReservedOrder $parameters);

    /**
     * @param CheckStatusOrder $parameters
     *
     * @return CheckStatusOrderResponse
     */
    public function checkStatusOrder(CheckStatusOrder $parameters);
}
