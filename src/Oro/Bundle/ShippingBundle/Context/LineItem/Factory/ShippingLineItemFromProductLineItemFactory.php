<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;

/**
 * Creates:
 *  - instance of {@see ShippingLineItem} by {@see ProductLineItemInterface};
 *  - collection of {@see ShippingLineItem} by iterable {@see ProductLineItemInterface}.
 */
class ShippingLineItemFromProductLineItemFactory implements ShippingLineItemFromProductLineItemFactoryInterface
{
    private ManagerRegistry $managerRegistry;

    private ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface $shippingKitItemLineItemFactory;

    /** @var LengthUnit[] */
    private array $dimensionsUnits = [];

    /** @var WeightUnit[] */
    private array $weightUnits = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface $shippingKitItemLineItemFactory
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->shippingKitItemLineItemFactory = $shippingKitItemLineItemFactory;
    }

    public function create(ProductLineItemInterface $productLineItem): ShippingLineItem
    {
        $shippingOptions = $this->getShippingOptionsIndexedByProductId([$productLineItem]);

        $shippingLineItem = $this->createShippingLineItem($productLineItem, $shippingOptions);

        $this->clearUnits();

        return $shippingLineItem;
    }

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     *
     * @return Collection<ShippingLineItem>
     */
    public function createCollection(iterable $productLineItems): Collection
    {
        $shippingOptions = $this->getShippingOptionsIndexedByProductId($productLineItems);

        $shippingLineItems = [];
        foreach ($productLineItems as $productLineItem) {
            $shippingLineItems[] = $this->createShippingLineItem($productLineItem, $shippingOptions);
        }

        $this->clearUnits();

        return new ArrayCollection($shippingLineItems);
    }

    /**
     * @param ProductLineItemInterface $productLineItem
     * @param array $shippingOptions
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
     *      ...
     *  ]
     *
     * @return ShippingLineItem
     */
    protected function createShippingLineItem(
        ProductLineItemInterface $productLineItem,
        array $shippingOptions
    ): ShippingLineItem {
        $product = $productLineItem->getProduct();

        $shippingLineItem = (new ShippingLineItem(
            $productLineItem->getProductUnit(),
            $productLineItem->getQuantity(),
            $productLineItem
        ))
            ->setProduct($product)
            ->setProductSku($productLineItem->getProductSku());

        if ($productLineItem instanceof PriceAwareInterface) {
            $shippingLineItem->setPrice($productLineItem->getPrice());
        }

        if ($productLineItem instanceof ProductKitItemLineItemsAwareInterface) {
            $shippingLineItem->setChecksum($productLineItem->getChecksum())
                ->setKitItemLineItems(
                    $this->shippingKitItemLineItemFactory->createCollection(
                        $productLineItem->getKitItemLineItems()
                    )
                );
        }

        $unitCode = $productLineItem->getProductUnit()->getCode();
        if ($product !== null && $unitCode !== null && isset($shippingOptions[$product->getId()][$unitCode])) {
            $shippingOptions = $shippingOptions[$product->getId()][$unitCode];
            // this shipping option is not the actual option.
            $shippingLineItem->setWeight(
                Weight::create(
                    $shippingOptions['weightValue'],
                    $this->getWeightUnit($shippingOptions['weightUnit'])
                )
            );

            $shippingLineItem->setDimensions(
                Dimensions::create(
                    $shippingOptions['dimensionsLength'],
                    $shippingOptions['dimensionsWidth'],
                    $shippingOptions['dimensionsHeight'],
                    $this->getDimensionsUnit($shippingOptions['dimensionsUnit'])
                )
            );
        }

        return $shippingLineItem;
    }

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     *
     * @return array
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
     *      ...
     *  ]
     */
    protected function getShippingOptionsIndexedByProductId(iterable $productLineItems): array
    {
        $unitsByProductIds = $this->getUnitsIndexedByProductId($productLineItems);

        return $this->getShippingOptionsRepository()
            ->findIndexedByProductsAndUnits($unitsByProductIds);
    }

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
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
    private function getUnitsIndexedByProductId(iterable $productLineItems): array
    {
        $result = [];
        foreach ($productLineItems as $productLineItem) {
            $product = $productLineItem->getProduct();
            $unit = $productLineItem->getProductUnit();
            if ($product !== null && $unit !== null) {
                $result[$product->getId()][$unit->getCode()] = $unit;
            }
        }

        return $result;
    }

    private function getShippingOptionsRepository(): ProductShippingOptionsRepository
    {
        return $this->managerRegistry->getRepository(ProductShippingOptions::class);
    }

    private function getDimensionsUnit(?string $lengthUnitCode): ?LengthUnit
    {
        if (!$lengthUnitCode) {
            return null;
        }

        if (!isset($this->dimensionsUnits[$lengthUnitCode])) {
            $this->dimensionsUnits[$lengthUnitCode] = $this->managerRegistry->getManagerForClass(LengthUnit::class)
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
            $this->weightUnits[$weightUnitCode] = $this->managerRegistry->getManagerForClass(WeightUnit::class)
                ->getReference(WeightUnit::class, $weightUnitCode);
        }

        return $this->weightUnits[$weightUnitCode];
    }

    protected function clearUnits(): void
    {
        $this->dimensionsUnits = [];
        $this->weightUnits = [];
    }
}
