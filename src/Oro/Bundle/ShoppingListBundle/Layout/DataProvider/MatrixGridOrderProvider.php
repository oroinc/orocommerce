<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
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
     * @var TotalProcessorProvider
     */
    private $totalProvider;

    /**
     * @var NumberFormatter
     */
    private $numberFormatter;

    /**
     * @param MatrixGridOrderManager $matrixGridManager
     * @param TotalProcessorProvider $totalProvider
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(
        MatrixGridOrderManager $matrixGridManager,
        TotalProcessorProvider $totalProvider,
        NumberFormatter $numberFormatter
    ) {
        $this->matrixGridManager = $matrixGridManager;
        $this->totalProvider = $totalProvider;
        $this->numberFormatter = $numberFormatter;
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
            foreach ($row->columns as $column) {
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
                $lineItem->setQuantity($column->quantity ?: 0);

                $shoppingList->addLineItem($lineItem);
            }
        }

        $price = $this->totalProvider->getTotal($shoppingList)->getTotalPrice();

        return $this->numberFormatter->formatCurrency(
            $price->getValue(),
            $price->getCurrency()
        );
    }
}
