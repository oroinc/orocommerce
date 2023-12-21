<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Modifier service for shipping line items
 */
class ShippingLineItemOptionsModifier
{
    /**
     *  [
     *      product id => [
     *          product unit code => [
     *              'dimensionsHeight' => float,
     *              'dimensionsLength' => float,
     *              'dimensionsWidth' => float,
     *              'dimensionsUnit' => string,
     *              'weightUnit' => string,
     *              'weightValue' => float,
     *              'code' => string,
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     *
     * Example:
     *  [
     *      1 => [
     *          'item' => [
     *              'dimensionsHeight' => 1.0,
     *              'dimensionsLength' => 1.0,
     *              'dimensionsWidth' => 1.0,
     *              'dimensionsUnit' => 'inch',
     *              'weightUnit' => 'lbs',
     *              'weightValue' => 1.0,
     *              'code' => 'item',
     *          ],
     *          ...
     *      ],
     *      2 => [
     *          'item' => [],
     *      ],
     *      ...
     *  ]
     */
    private array $shippingOptions = [];

    /** @var LengthUnit[] */
    private array $dimensionsUnits = [];

    /** @var WeightUnit[] */
    private array $weightUnits = [];

    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    public function modifyLineItemWithShippingOptions(
        ProductShippingOptionsInterface $lineItem
    ): void {
        $product = $lineItem->getProduct();
        $unitCode = $lineItem->getProductUnit()?->getCode();

        if (!$product || !$unitCode) {
            return;
        }

        if (!isset($this->shippingOptions[$product->getId()][$unitCode])) {
            $this->loadShippingOptions([$lineItem]);
        }

        $shippingOptions = $this->shippingOptions[$product->getId()][$unitCode] ?? [];
        if (!empty($shippingOptions)) {
            // this shipping option is not the actual option.
            $lineItem->setWeight(
                Weight::create(
                    $shippingOptions['weightValue'],
                    $this->getWeightUnit($shippingOptions['weightUnit'])
                )
            );

            $lineItem->setDimensions(
                Dimensions::create(
                    $shippingOptions['dimensionsLength'],
                    $shippingOptions['dimensionsWidth'],
                    $shippingOptions['dimensionsHeight'],
                    $this->getDimensionsUnit($shippingOptions['dimensionsUnit'])
                )
            );
        }

        $kitItemLineItems = $this->getKitItemLineItems([$lineItem]);
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $this->modifyLineItemWithShippingOptions($kitItemLineItem);
        }
    }

    /**
     * @param iterable<ProductLineItemInterface|ProductShippingOptionsInterface> $lineItems
     */
    public function loadShippingOptions(iterable $lineItems): void
    {
        $lineItems = array_merge($lineItems, $this->getKitItemLineItems($lineItems));
        $unitsByProductIds = $this->getUnitsIndexedByProductId($lineItems);

        $this->shippingOptions = $this->getShippingOptionsRepository()
            ->findIndexedByProductsAndUnits($unitsByProductIds);

        foreach ($unitsByProductIds as $productId => $unitCodes) {
            foreach ($unitCodes as $unitCode => $productUnit) {
                if (!isset($this->shippingOptions[$productId][$unitCode])) {
                    $this->shippingOptions[$productId][$unitCode] = [];
                }
            }
        }
    }

    public function clear(): void
    {
        $this->shippingOptions = [];
        $this->dimensionsUnits = [];
        $this->weightUnits = [];
    }

    /**
     * @param iterable<ProductLineItemInterface|ProductShippingOptionsInterface> $lineItems
     *
     * @return array
     *  [
     *      product id => [
     *          product unit code => Product Unit,
     *          ...
     *      ],
     *      ...
     *  ]
     *
     * Example:
     *  [
     *      1 => [
     *          'item' => Product Unit object,
     *          ...
     *      ],
     *      ...
     *  ]
     */
    private function getUnitsIndexedByProductId(iterable $lineItems): array
    {
        $result = [];
        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();
            $unit = $lineItem->getProductUnit();
            if ($product !== null && $unit !== null) {
                $result[$product->getId()][$unit->getCode()] = $unit;
            }
        }

        return $result;
    }

    private function getDimensionsUnit(?string $lengthUnitCode): ?LengthUnit
    {
        if (!$lengthUnitCode) {
            return null;
        }

        if (!isset($this->dimensionsUnits[$lengthUnitCode])) {
            $this->dimensionsUnits[$lengthUnitCode] = $this->registry->getManagerForClass(LengthUnit::class)
                ->getReference(LengthUnit::class, $lengthUnitCode);
        }

        return $this->dimensionsUnits[$lengthUnitCode];
    }

    private function getWeightUnit(?string $weightUnitCode): ?WeightUnit
    {
        if (!$weightUnitCode) {
            return null;
        }

        if (!isset($this->weightUnits[$weightUnitCode])) {
            $this->weightUnits[$weightUnitCode] = $this->registry->getManagerForClass(WeightUnit::class)
                ->getReference(WeightUnit::class, $weightUnitCode);
        }

        return $this->weightUnits[$weightUnitCode];
    }

    /**
     * @param ProductLineItemInterface[]|ProductShippingOptionsInterface[] $lineItems
     *
     * @return ShippingKitItemLineItem[]
     */
    private function getKitItemLineItems(array $lineItems): array
    {
        $kitLineItems = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof ProductKitItemLineItemsAwareInterface && $lineItem->getProduct()?->isKit()) {
                $kitLineItems = array_merge($kitLineItems, $lineItem->getKitItemLineItems()->toArray());
            }
        }

        return $kitLineItems;
    }

    private function getShippingOptionsRepository(): ProductShippingOptionsRepository
    {
        return $this->registry->getRepository(ProductShippingOptions::class);
    }
}
