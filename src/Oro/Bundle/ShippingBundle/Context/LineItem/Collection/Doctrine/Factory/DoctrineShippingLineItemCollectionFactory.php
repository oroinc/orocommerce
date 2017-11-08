<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\Factory;

use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

class DoctrineShippingLineItemCollectionFactory implements ShippingLineItemCollectionFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createShippingLineItemCollection(array $shippingLineItems): ShippingLineItemCollectionInterface
    {
        foreach ($shippingLineItems as $shippingLineItem) {
            if (!$shippingLineItem instanceof ShippingLineItemInterface) {
                throw new \InvalidArgumentException(
                    sprintf('Expected: %s', ShippingLineItemInterface::class)
                );
            }
        }

        return new DoctrineShippingLineItemCollection($shippingLineItems);
    }
}
