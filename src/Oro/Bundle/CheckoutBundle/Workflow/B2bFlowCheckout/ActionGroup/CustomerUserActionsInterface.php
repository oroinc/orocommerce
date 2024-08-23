<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Checkout workflow Customer User-related actions.
 */
interface CustomerUserActionsInterface
{
    public function createGuestCustomerUser(
        Checkout $checkout,
        string $email = null,
        AbstractAddress $billingAddress = null
    ): void;

    public function updateGuestCustomerUser(
        Checkout $checkout,
        string $email = null,
        AbstractAddress $billingAddress = null
    ): void;

    /**
     * @param Checkout $checkout
     * @param Order $order
     * @param null|array{
     *     is_late_registration_enabled: bool,
     *     email: string,
     *     password: string
     * } $lateRegistrationData
     *
     * @return array{
     *     result: array{confirmationRequired: bool}
     * }
     */
    public function handleLateRegistration(Checkout $checkout, Order $order, ?array $lateRegistrationData = []): array;
}
