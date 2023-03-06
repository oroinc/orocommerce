<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Provides split entities for Checkout and Order entities.
 */
class SplitEntitiesProvider implements SplitEntitiesProviderInterface
{
    private SplitCheckoutProvider $splitCheckoutProvider;

    public function __construct(SplitCheckoutProvider $splitCheckoutProvider)
    {
        $this->splitCheckoutProvider = $splitCheckoutProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getSplitEntities(ProductLineItemsHolderInterface $entity): array
    {
        if ($entity instanceof Checkout) {
            return $this->splitCheckoutProvider->getSubCheckouts($entity);
        }

        if ($entity instanceof Order) {
            return $entity->getSubOrders()->toArray();
        }

        return [];
    }
}
