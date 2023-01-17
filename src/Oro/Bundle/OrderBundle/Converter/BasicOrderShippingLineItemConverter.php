<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

/**
 * Converts order line items to a collection of shipping line items.
 */
class BasicOrderShippingLineItemConverter implements OrderShippingLineItemConverterInterface
{
    private ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory;
    private ShippingLineItemBuilderFactoryInterface $shippingLineItemBuilderFactory;

    public function __construct(
        ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory,
        ShippingLineItemBuilderFactoryInterface $shippingLineItemBuilderFactory
    ) {
        $this->shippingLineItemCollectionFactory = $shippingLineItemCollectionFactory;
        $this->shippingLineItemBuilderFactory = $shippingLineItemBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function convertLineItems(Collection $orderLineItems): ShippingLineItemCollectionInterface
    {
        $shippingLineItems = [];
        foreach ($orderLineItems as $orderLineItem) {
            if ($orderLineItem->getProductUnit() === null) {
                $shippingLineItems = [];
                break;
            }

            $builder = $this->shippingLineItemBuilderFactory->createBuilder(
                $orderLineItem->getProductUnit(),
                $orderLineItem->getProductUnit()->getCode(),
                $orderLineItem->getQuantity(),
                $orderLineItem
            );
            if (null !== $orderLineItem->getProduct()) {
                $builder->setProduct($orderLineItem->getProduct());
            }
            if (null !== $orderLineItem->getPrice()) {
                $builder->setPrice($orderLineItem->getPrice());
            }
            $shippingLineItems[] = $builder->getResult();
        }

        return $this->shippingLineItemCollectionFactory->createShippingLineItemCollection($shippingLineItems);
    }
}
