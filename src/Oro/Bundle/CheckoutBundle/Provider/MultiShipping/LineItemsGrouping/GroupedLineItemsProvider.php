<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\ShippingBundle\Provider\GroupLineItemHelperInterface;

/**
 * Provides grouped checkout line items according to configured grouping field.
 */
class GroupedLineItemsProvider implements GroupedLineItemsProviderInterface
{
    private GroupLineItemHelperInterface $groupLineItemHelper;
    private array $cachedGroupedLineItems = [];

    public function __construct(GroupLineItemHelperInterface $groupLineItemHelper)
    {
        $this->groupLineItemHelper = $groupLineItemHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupedLineItems(ProductLineItemsHolderInterface $entity): array
    {
        $cacheKey = spl_object_hash($entity);
        if (!isset($this->cachedGroupedLineItems[$cacheKey])) {
            $this->cachedGroupedLineItems[$cacheKey] = $this->groupLineItemHelper->getGroupedLineItems(
                $entity->getLineItems(),
                $this->groupLineItemHelper->getGroupingFieldPath()
            );
        }

        return $this->cachedGroupedLineItems[$cacheKey];
    }
}
