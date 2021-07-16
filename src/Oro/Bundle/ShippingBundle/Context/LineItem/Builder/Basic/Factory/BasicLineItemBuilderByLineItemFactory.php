<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Basic\Factory;

use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\LineItemBuilderByLineItemFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\ShippingLineItemBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

class BasicLineItemBuilderByLineItemFactory implements LineItemBuilderByLineItemFactoryInterface
{
    /**
     * @var ShippingLineItemBuilderFactoryInterface
     */
    private $builderFactory;

    public function __construct(ShippingLineItemBuilderFactoryInterface $builderFactory)
    {
        $this->builderFactory = $builderFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createBuilder(ShippingLineItemInterface $lineItem): ShippingLineItemBuilderInterface
    {
        $builder = $this->builderFactory->createBuilder(
            $lineItem->getProductUnit(),
            $lineItem->getProductUnitCode(),
            $lineItem->getQuantity(),
            $lineItem->getProductHolder()
        );

        if (null !== $lineItem->getProduct()) {
            $builder->setProduct($lineItem->getProduct());
        }

        if (null !== $lineItem->getProductSku()) {
            $builder->setProductSku($lineItem->getProductSku());
        }

        if (null !== $lineItem->getPrice()) {
            $builder->setPrice($lineItem->getPrice());
        }

        if (null !== $lineItem->getWeight()) {
            $builder->setWeight($lineItem->getWeight());
        }

        if (null !== $lineItem->getDimensions()) {
            $builder->setDimensions($lineItem->getDimensions());
        }

        return $builder;
    }
}
