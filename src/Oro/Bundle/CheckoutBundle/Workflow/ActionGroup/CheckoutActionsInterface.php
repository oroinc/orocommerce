<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Checkout workflow Checkout-related actions.
 */
interface CheckoutActionsInterface
{
    public function getCheckoutUrl(Checkout $checkout, ?string $transition = null): string;

    /**
     * @param Checkout $checkout
     * @param Order $order
     * @param array{
     *     successUrl?: string,
     *     failureUrl?: string,
     *     partiallyPaidUrl?: string,
     *     failedShippingAddressUrl?: string,
     *     checkoutId?: int
     * } $transactionOptions
     *
     * @return array{responseData: array}
     */
    public function purchase(Checkout $checkout, Order $order, array $transactionOptions = []): array;

    public function finishCheckout(
        Checkout $checkout,
        Order $order,
        bool $autoRemoveSource = false,
        bool $allowManualSourceRemove = false,
        bool $removeSource = false,
        bool $clearSource = false
    ): void;

    public function sendConfirmationEmail(Checkout $checkout, Order $order): void;

    public function finalizeSourceEntity(
        Checkout $checkout,
        bool $autoRemoveSource = false,
        bool $allowManualSourceRemove = false,
        bool $removeSource = false,
        bool $clearSource = false
    ): void;

    public function fillCheckoutCompletedData(Checkout $checkout, Order $order): void;
}
