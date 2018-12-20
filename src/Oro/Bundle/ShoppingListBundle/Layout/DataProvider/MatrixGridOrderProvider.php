<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;

/**
 * Provides data for matrix order grid for layouts.
 */
class MatrixGridOrderProvider
{
    /**
     * @var MatrixGridOrderManager
     */
    private $matrixGridManager;

    /**
     * @var TotalProcessorProvider
     */
    private $totalProvider;

    /**
     * @var NumberFormatter
     */
    private $numberFormatter;

    /**
     * @var CurrentShoppingListManager
     */
    private $currentShoppingListManager;

    /**
     * @param MatrixGridOrderManager $matrixGridManager
     * @param TotalProcessorProvider $totalProvider
     * @param NumberFormatter $numberFormatter
     * @param CurrentShoppingListManager $currentShoppingListManager
     */
    public function __construct(
        MatrixGridOrderManager $matrixGridManager,
        TotalProcessorProvider $totalProvider,
        NumberFormatter $numberFormatter,
        CurrentShoppingListManager $currentShoppingListManager
    ) {
        $this->matrixGridManager = $matrixGridManager;
        $this->totalProvider = $totalProvider;
        $this->numberFormatter = $numberFormatter;
        $this->currentShoppingListManager = $currentShoppingListManager;
    }

    /**
     * Get total quantities for all columns and per column
     *
     * @param Product $product
     * @param ShoppingList $shoppingList
     * @return float
     */
    public function getTotalQuantity(Product $product, ShoppingList $shoppingList = null)
    {
        $shoppingList = $shoppingList ?: $this->currentShoppingListManager->getCurrent();

        $collection = $this->matrixGridManager->getMatrixCollection($product, $shoppingList);

        $totalQuantity = 0;
        foreach ($collection->rows as $row) {
            foreach ($row->columns as $column) {
                $totalQuantity += $column->quantity;
            }
        }

        return $totalQuantity;
    }

    /**
     * @param Product $product
     * @param ShoppingList $shoppingList
     * @return string
     */
    public function getTotalPriceFormatted(Product $product, ShoppingList $shoppingList = null)
    {
        $shoppingList = $shoppingList ?: $this->currentShoppingListManager->getCurrent();

        $collection = $this->matrixGridManager->getMatrixCollection($product, $shoppingList);

        $tempShoppingList = new ShoppingList();

        foreach ($collection->rows as $row) {
            foreach ($row->columns as $column) {
                if ($column->product === null) {
                    continue;
                }

                $lineItem = new LineItem();
                $lineItem->setProduct($column->product);
                $lineItem->setUnit($collection->unit);
                $lineItem->setQuantity($column->quantity ?: 0);

                $tempShoppingList->addLineItem($lineItem);
            }
        }

        $price = $this->totalProvider->getTotal($tempShoppingList)->getTotalPrice();

        return $this->numberFormatter->formatCurrency(
            $price->getValue(),
            $price->getCurrency()
        );
    }

    /**
     * @param Product[] $products
     * @param ShoppingList $shoppingList
     * @return array
     */
    public function getTotalsQuantityPrice(array $products, ShoppingList $shoppingList = null)
    {
        $totals = [];

        foreach ($products as $product) {
            if ($product->getType() !== Product::TYPE_CONFIGURABLE) {
                continue;
            }

            $totals[$product->getId()] = [
                'quantity' => $this->getTotalQuantity($product, $shoppingList),
                'price' => $this->getTotalPriceFormatted($product, $shoppingList),
            ];
        }

        return $totals;
    }
}
