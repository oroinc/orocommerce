<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents a service that provides an user that should be used as an owner for child order.
 */
interface SubOrderOwnerProviderInterface
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
     * @return User
     */
    public function getOwner(Collection $lineItems, string $groupingPath): User;
}
