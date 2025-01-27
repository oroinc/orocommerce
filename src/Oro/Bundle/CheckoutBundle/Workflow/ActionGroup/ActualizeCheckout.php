<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Model\CheckoutBySourceCriteriaManipulatorInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Actualize checkout data.
 */
class ActualizeCheckout implements ActualizeCheckoutInterface
{
    public function __construct(
        private UserCurrencyManager $userCurrencyManager,
        private CheckoutBySourceCriteriaManipulatorInterface $checkoutBySourceCriteriaManipulator
    ) {
    }

    #[\Override]
    public function execute(
        Checkout $checkout,
        array $sourceCriteria,
        ?Website $currentWebsite,
        bool $updateData = false,
        array $checkoutData = []
    ): Checkout {
        return $this->checkoutBySourceCriteriaManipulator->actualizeCheckout(
            $checkout,
            $currentWebsite,
            $sourceCriteria,
            $this->userCurrencyManager->getUserCurrency(),
            $checkoutData,
            $updateData
        );
    }
}
