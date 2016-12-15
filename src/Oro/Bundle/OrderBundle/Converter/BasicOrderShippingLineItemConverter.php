<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class BasicOrderShippingLineItemConverter implements OrderShippingLineItemConverterInterface
{
    /**
     * @var ShippingLineItemCollectionFactoryInterface|null
     */
    private $shippingLineItemCollectionFactory = null;

    /**
     * @param ShippingLineItemCollectionFactoryInterface|null $shippingLineItemCollectionFactory
     */
    public function __construct(ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory = null)
    {
        $this->shippingLineItemCollectionFactory = $shippingLineItemCollectionFactory;
    }

    /**
     * @param OrderLineItem[]|Collection $orderLineItems
     * {@inheritDoc}
     */
    public function convertLineItems(Collection $orderLineItems)
    {
        if (null === $this->shippingLineItemCollectionFactory) {
            return null;
        }

        $shippingLineItems = [];

        foreach ($orderLineItems as $orderLineItem) {
            $shippingLineItem = new ShippingLineItem();

            $shippingLineItem->setProduct($orderLineItem->getProduct());
            $shippingLineItem->setProductHolder($orderLineItem->getProductHolder());
            $shippingLineItem->setProductUnit($orderLineItem->getProductUnit());
            $shippingLineItem->setQuantity($orderLineItem->getQuantity());
            $shippingLineItem->setPrice($orderLineItem->getPrice());

            $shippingLineItems[] = $shippingLineItem;
        }

        return $this->shippingLineItemCollectionFactory->createShippingLineItemCollection($shippingLineItems);
    }
}
