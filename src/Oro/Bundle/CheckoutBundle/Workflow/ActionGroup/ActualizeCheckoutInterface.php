<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Actualize checkout data.
 */
interface ActualizeCheckoutInterface
{
    public function execute(
        Checkout $checkout,
        array $sourceCriteria,
        ?Website $currentWebsite,
        bool $updateData = false,
        array $checkoutData = []
    ): Checkout;
}
