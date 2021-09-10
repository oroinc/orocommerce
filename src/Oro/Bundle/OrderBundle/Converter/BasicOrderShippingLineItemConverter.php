<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;

class BasicOrderShippingLineItemConverter implements OrderShippingLineItemConverterInterface
{
    /**
     * @var ShippingLineItemCollectionFactoryInterface|null
     */
    private $shippingLineItemCollectionFactory = null;

    /**
     * @var ShippingLineItemBuilderFactoryInterface|null
     */
    private $shippingLineItemBuilderFactory = null;

    public function __construct(
        ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory = null,
        ShippingLineItemBuilderFactoryInterface $shippingLineItemBuilderFactory = null
    ) {
        $this->shippingLineItemCollectionFactory = $shippingLineItemCollectionFactory;
        $this->shippingLineItemBuilderFactory = $shippingLineItemBuilderFactory;
    }

    /**
     * @param OrderLineItem[]|Collection $orderLineItems
     * {@inheritDoc}
     */
    public function convertLineItems(Collection $orderLineItems)
    {
        if (null === $this->shippingLineItemCollectionFactory || null === $this->shippingLineItemBuilderFactory) {
            return null;
        }

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
