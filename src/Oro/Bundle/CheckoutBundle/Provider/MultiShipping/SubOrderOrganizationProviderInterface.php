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
     * Possible values for $groupingPath:
     * * '<property path>:<property value>'
     * * 'other-items'
     * {@see LineItemsGrouping\GroupLineItemsByConfiguredFields::getGroupedLineItems} for details.
     *
     * @param Collection<int, CheckoutLineItem> $lineItems
     * @param string                            $groupingPath 'product.owner:1' or 'other-items' for example
     *
     * @return Organization
     */
    public function getOrganization(Collection $lineItems, string $groupingPath): Organization;
}
