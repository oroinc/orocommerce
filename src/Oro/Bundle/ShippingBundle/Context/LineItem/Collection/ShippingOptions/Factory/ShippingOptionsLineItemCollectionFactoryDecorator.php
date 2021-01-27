<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingOptions\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\LineItemBuilderByLineItemFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\ShippingLineItemBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Sets shipping options for the shipping line items when options value is null.
 */
class ShippingOptionsLineItemCollectionFactoryDecorator implements ShippingLineItemCollectionFactoryInterface
{
    /** @var ShippingLineItemCollectionFactoryInterface */
    private $decoratedFactory;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ShippingLineItemBuilderFactoryInterface */
    private $builderByLineItemFactory;

    /** @var LengthUnit */
    private $dimensionsUnits = [];

    /** @var WeightUnit[] */
    private $weightUnits = [];

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

        if (count($shippingOptionsByProductId) === 0) {
            return $this->decoratedFactory->createShippingLineItemCollection($shippingLineItems);
        }

        $newShippingLineItems = [];

        foreach ($shippingLineItems as $lineItem) {
            $builder = $this->builderByLineItemFactory->createBuilder($lineItem);

            $product = $lineItem->getProduct();

            if ($product !== null && array_key_exists($product->getId(), $shippingOptionsByProductId)) {
                $shippingOptions = $shippingOptionsByProductId[$product->getId()];

                if ($lineItem->getWeight() === null) {
                    $builder->setWeight(
                        Weight::create(
                            $shippingOptions['weightValue'],
                            $this->getWeightUnit($shippingOptions)
                        )
                    );
                }

                if ($lineItem->getDimensions() === null) {
                    $builder->setDimensions(
                        Dimensions::create(
                            $shippingOptions['dimensionsLength'],
                            $shippingOptions['dimensionsWidth'],
                            $shippingOptions['dimensionsHeight'],
                            $this->getDimensionsUnit($shippingOptions)
                        )
                    );
                }
            }

            $newShippingLineItems[] = $builder->getResult();
        }

        $this->dimensionsUnits = [];
        $this->weightUnits = [];

        return $this->decoratedFactory->createShippingLineItemCollection($newShippingLineItems);
    }

    /**
     * @param ShippingLineItemInterface[] $shippingLineItems
     *
     * @return array
     */
    private function getShippingOptionsIndexedByProductId(array $shippingLineItems): array
    {
        $unitsByProductIds = $this->getUnitsIndexedByProductId($shippingLineItems);

        return $this->getShippingOptionsRepository()
            ->findIndexedByProductsAndUnits($unitsByProductIds);
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

    /**
     * @param array $shippingOptions
     * @return LengthUnit
     */
    private function getDimensionsUnit(array $shippingOptions): LengthUnit
    {
        $lengthUnitCode = $shippingOptions['dimensionsUnit'];
        if (!isset($this->dimensionsUnits[$lengthUnitCode])) {
            $this->dimensionsUnits[$lengthUnitCode] = $this->doctrineHelper->getEntityReference(
                LengthUnit::class,
                $lengthUnitCode
            );
        }

        return $this->dimensionsUnits[$lengthUnitCode];
    }

    /**
     * @param array $shippingOptions
     * @return WeightUnit
     */
    private function getWeightUnit(array $shippingOptions): WeightUnit
    {
        $weightUnitCode = $shippingOptions['weightUnit'];
        if (!isset($this->weightUnits[$weightUnitCode])) {
            $this->weightUnits[$weightUnitCode] = $this->doctrineHelper->getEntityReference(
                WeightUnit::class,
                $weightUnitCode
            );
        }

        return $this->weightUnits[$weightUnitCode];
    }
}
