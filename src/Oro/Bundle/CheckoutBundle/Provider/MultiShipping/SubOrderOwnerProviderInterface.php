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
     * @param Collection<int, CheckoutLineItem> $lineItems
     * @param string                            $lineItemGroupKey
     *
     * @return User
     */
    public function getOwner(Collection $lineItems, string $lineItemGroupKey): User;
}
