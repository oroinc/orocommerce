<?php

namespace Oro\Bundle\CheckoutBundle\Model;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Manipulate Checkout entity by a given Checkout source criteria.
 */
interface CheckoutBySourceCriteriaManipulatorInterface
{
    public function createCheckout(
        Website $website,
        array $sourceCriteria,
        ?UserInterface $customerUser = null,
        ?string $currency = null,
        array $checkoutData = []
    ): Checkout;

    public function findCheckout(
        array $sourceCriteria,
        ?UserInterface $customerUser,
        ?string $currency,
        ?string $workflowName = null
    ): ?Checkout;

    public function actualizeCheckout(
        Checkout $checkout,
        ?Website $website,
        array $sourceCriteria,
        ?string $currency,
        array $checkoutData = [],
        bool $updateData = false
    ): Checkout;
}
