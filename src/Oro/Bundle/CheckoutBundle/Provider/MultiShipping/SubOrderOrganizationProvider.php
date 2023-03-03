<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Provides an organization in which a child order should be created.
 */
class SubOrderOrganizationProvider implements SubOrderOrganizationProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrganization(Collection $lineItems, string $groupingPath): Organization
    {
        /** @var CheckoutLineItem|false $lineItem */
        $lineItem = $lineItems->first();

        $organization = null;
        if ($lineItem) {
            $organization = $lineItem->getCheckout()->getOrganization();
        }
        if (null === $organization) {
            throw new \LogicException('Unable to determine order organization.');
        }

        return $organization;
    }
}
