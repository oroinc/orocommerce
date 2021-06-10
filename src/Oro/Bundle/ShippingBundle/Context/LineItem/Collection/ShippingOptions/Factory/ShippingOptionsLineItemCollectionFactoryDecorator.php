<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingOptions\Factory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\LineItem\Builder\Factory\LineItemBuilderByLineItemFactoryInterface;
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
    private ShippingLineItemCollectionFactoryInterface $decoratedFactory;

    private DoctrineHelper $doctrineHelper;

    private LineItemBuilderByLineItemFactoryInterface $builderByLineItemFactory;

    /** @var LengthUnit[] */
    private array $dimensionsUnits = [];

    /** @var WeightUnit[] */
    private array $weightUnits = [];

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
     * {@inheritdoc}
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
                            $this->getWeightUnit($shippingOptions['weightUnit'])
                        )
                    );
                }

                if ($lineItem->getDimensions() === null) {
                    $builder->setDimensions(
                        Dimensions::create(
                            $shippingOptions['dimensionsLength'],
                            $shippingOptions['dimensionsWidth'],
                            $shippingOptions['dimensionsHeight'],
                            $this->getDimensionsUnit($shippingOptions['dimensionsUnit'])
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

    private function getShippingOptionsRepository(): ProductShippingOptionsRepository
    {
        return $this->doctrineHelper->getEntityRepository(ProductShippingOptions::class);
    }

    private function getDimensionsUnit(?string $lengthUnitCode): ?LengthUnit
    {
        if (!$lengthUnitCode) {
            return null;
        }

        if (!isset($this->dimensionsUnits[$lengthUnitCode])) {
            $this->dimensionsUnits[$lengthUnitCode] = $this->doctrineHelper
                ->getEntityReference(LengthUnit::class, $lengthUnitCode);
        }

        return $this->dimensionsUnits[$lengthUnitCode];
    }

    private function getWeightUnit(?string $weightUnitCode): ?WeightUnit
    {
        if (!$weightUnitCode) {
            return null;
        }

        if (!isset($this->weightUnits[$weightUnitCode])) {
            $this->weightUnits[$weightUnitCode] = $this->doctrineHelper
                ->getEntityReference(WeightUnit::class, $weightUnitCode);
        }

        return $this->weightUnits[$weightUnitCode];
    }
}
