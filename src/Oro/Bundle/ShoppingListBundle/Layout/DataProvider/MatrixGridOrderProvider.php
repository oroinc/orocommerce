<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;

class MatrixGridOrderProvider
{
    /**
     * @var MatrixGridOrderManager
     */
    private $matrixGridManager;

    /**
     * @var ProductVariantAvailabilityProvider
     */
    private $productVariantAvailability;

    /**
     * @var TotalProcessorProvider
     */
    private $totalProvider;

    /**
     * @var NumberFormatter
     */
    private $numberFormatter;

    /**
     * @param MatrixGridOrderManager $matrixGridManager
     * @param ProductVariantAvailabilityProvider $productVariantAvailability
     * @param TotalProcessorProvider $totalProvider
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(
        MatrixGridOrderManager $matrixGridManager,
        ProductVariantAvailabilityProvider $productVariantAvailability,
        TotalProcessorProvider $totalProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->matrixGridManager = $matrixGridManager;
        $this->productVariantAvailability = $productVariantAvailability;
        $this->totalProvider = $totalProvider;
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isAvailable(Product $product)
    {
        try {
            $variants = $this->productVariantAvailability->getVariantFieldsAvailability($product);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        reset($variants);
        $variantsCount = count($variants);
        if ($variantsCount > 2 || count(end($variants)) > 5) {
            return false;
        }

        if ($variantsCount === 1 && count(reset($variants)) < 2) {
            return false;
        }

        $configurableProductPrimaryUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $simpleProducts = $this->productVariantAvailability->getSimpleProductsByVariantFields($product);
        foreach ($simpleProducts as $simpleProduct) {
            if (!$this->doSimpleProductSupportsUnitPrecision($simpleProduct, $configurableProductPrimaryUnit)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get total quantities for all columns and per column
     *
     * @param Product $product
     * @return float
     */
    public function getTotalQuantity(Product $product)
    {
        $collection = $this->matrixGridManager->getMatrixCollection($product);

        $totalQuantity = 0;
        foreach ($collection->rows as $row) {
            foreach ($row->columns as $i => $column) {
                $totalQuantity += $column->quantity;
            }
        }

        return $totalQuantity;
    }

    /**
     * @param Product $product
     * @return string
     */
    public function getTotalPriceFormatted(Product $product)
    {
        $collection = $this->matrixGridManager->getMatrixCollection($product);

        $shoppingList = new ShoppingList();

        foreach ($collection->rows as $row) {
            foreach ($row->columns as $column) {
                if ($column->product === null) {
                    continue;
                }

                $lineItem = new LineItem();
                $lineItem->setProduct($column->product);
                $lineItem->setUnit($collection->unit);
                $lineItem->setQuantity($column->quantity);

                $shoppingList->addLineItem($lineItem);
            }
        }

        $price = $this->totalProvider->getTotal($shoppingList)->getTotalPrice();

        return $this->numberFormatter->formatCurrency(
            $price->getValue(),
            $price->getCurrency()
        );
    }

    /**
     * @param Product $product
     * @param ProductUnit $unit
     * @return bool
     */
    private function doSimpleProductSupportsUnitPrecision(Product $product, ProductUnit $unit)
    {
        $productUnits = $product->getUnitPrecisions()->map(
            function (ProductUnitPrecision $unitPrecision) {
                return $unitPrecision->getUnit();
            }
        );

        return $productUnits->contains($unit);
    }
}
