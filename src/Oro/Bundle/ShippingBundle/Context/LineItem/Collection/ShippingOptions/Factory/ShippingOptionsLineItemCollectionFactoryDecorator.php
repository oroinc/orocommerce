<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingOptions\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\LineItemBuilderByLineItemFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;

class ShippingOptionsLineItemCollectionFactoryDecorator implements ShippingLineItemCollectionFactoryInterface
{
    /**
     * @var ShippingLineItemCollectionFactoryInterface
     */
    private $decoratedFactory;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ShippingLineItemBuilderFactoryInterface
     */
    private $builderByLineItemFactory;

    /**
     * @param ShippingLineItemCollectionFactoryInterface $decoratedFactory
     * @param DoctrineHelper                             $doctrineHelper
     * @param LineItemBuilderByLineItemFactoryInterface  $builderByLineItemFactory
     */
    public function __construct(
        ShippingLineItemCollectionFactoryInterface $decoratedFactory,
        DoctrineHelper $doctrineHelper,
        LineItemBuilderByLineItemFactoryInterface $builderByLineItemFactory
    ) {
        $this->decoratedFactory = $decoratedFactory;
        $this->doctrineHelper = $doctrineHelper;
        $this->builderByLineItemFactory = $builderByLineItemFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function createShippingLineItemCollection(array $shippingLineItems): ShippingLineItemCollectionInterface
    {
        $shippingOptionsByProductId = $this->getShippingOptionsIndexedByProductId($shippingLineItems);

        $newShippingLineItems = [];

        foreach ($shippingLineItems as $lineItem) {
            $builder = $this->builderByLineItemFactory->createBuilder($lineItem);

            $product = $lineItem->getProduct();

            if ($product !== null && array_key_exists($product->getId(), $shippingOptionsByProductId)) {
                $shippingOptions = $shippingOptionsByProductId[$product->getId()];

                if ($lineItem->getWeight() === null) {
                    $builder->setWeight($shippingOptions->getWeight());
                }

                if ($lineItem->getDimensions() === null) {
                    $builder->setDimensions($shippingOptions->getDimensions());
                }
            }

            $newShippingLineItems[] = $builder->getResult();
        }

        return $this->decoratedFactory->createShippingLineItemCollection($newShippingLineItems);
    }

    /**
     * @param ShippingLineItemInterface[] $shippingLineItems
     *
     * @return ProductShippingOptions[]
     */
    private function getShippingOptionsIndexedByProductId(array $shippingLineItems): array
    {
        $unitsByProductIds = $this->getUnitsIndexedByProductId($shippingLineItems);
        $repository = $this->getShippingOptionsRepository();

        $shippingOptions = $repository->findByProductsAndUnits($unitsByProductIds);

        $result = [];

        foreach ($shippingOptions as $shippingOption) {
            $result[$shippingOption->getProduct()->getId()] = $shippingOption;
        }

        return $result;
    }

    /**
     * @param ShippingLineItemInterface[] $shippingLineItems
     *
     * @return array
     */
    private function getUnitsIndexedByProductId(array $shippingLineItems): array
    {
        $result = [];

        foreach ($shippingLineItems as $shippingLineItem) {
            $product = $shippingLineItem->getProduct();
            $unit = $shippingLineItem->getProductUnit();
            if ($product !== null && $unit !== null) {
                $result[$product->getId()] = $unit;
            }
        }

        return $result;
    }

    /**
     * @return ProductShippingOptionsRepository|\Doctrine\ORM\EntityRepository
     */
    private function getShippingOptionsRepository()
    {
        return $this->doctrineHelper->getEntityRepository(ProductShippingOptions::class);
    }
}
