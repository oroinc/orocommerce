<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Represents a service that provides an organization in which a child order should be created.
 */
interface SubOrderOrganizationProviderInterface
{
    /**
     * @param Collection<int, CheckoutLineItem> $lineItems
     * @param string                            $lineItemGroupKey
     *
     * @return Organization
     */
    public function getOrganization(Collection $lineItems, string $lineItemGroupKey): Organization;
}
