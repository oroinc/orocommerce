<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Basic interface for providers responsible for logic to retrieve split entities.
 */
interface SplitEntitiesProviderInterface
{
    public function getSplitEntities(ProductLineItemsHolderInterface $entity): array;
}
