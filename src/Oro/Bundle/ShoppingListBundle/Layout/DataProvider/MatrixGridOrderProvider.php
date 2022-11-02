<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;

/**
 * Provides data for matrix order grid for layouts.
 */
class MatrixGridOrderProvider
{
    private MatrixGridOrderManager $matrixGridManager;
    private TotalProcessorProvider $totalProvider;
    private NumberFormatter $numberFormatter;
    private CurrentShoppingListManager $currentShoppingListManager;
    private ManagerRegistry $doctrine;

    public function __construct(
        MatrixGridOrderManager $matrixGridManager,
        TotalProcessorProvider $totalProvider,
        NumberFormatter $numberFormatter,
        CurrentShoppingListManager $currentShoppingListManager,
        ManagerRegistry $doctrine
    ) {
        $this->matrixGridManager = $matrixGridManager;
        $this->totalProvider = $totalProvider;
        $this->numberFormatter = $numberFormatter;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->doctrine = $doctrine;
    }

    public function getTotalQuantity(Product $product, ShoppingList $shoppingList = null): float|int
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

    public function getTotalPriceFormatted(Product $product, ShoppingList $shoppingList = null): string
    {
        $shoppingList = $shoppingList ?: $this->currentShoppingListManager->getCurrent();

        $collection = $this->matrixGridManager->getMatrixCollection($product, $shoppingList);

        if (!$shoppingList) {
            $tempShoppingList = new ShoppingList();
        } else {
            $tempShoppingList = clone $shoppingList;
            $tempShoppingList->getLineItems()->clear();
        }

        foreach ($collection->rows as $row) {
            foreach ($row->columns as $column) {
                if ($column->product === null || !$column->quantity) {
                    continue;
                }

                $lineItem = new LineItem();
                $lineItem->setProduct($column->product);
                $lineItem->setUnit($collection->unit);
                $lineItem->setQuantity($column->quantity);

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
     * @param ProductView[]     $products
     * @param ShoppingList|null $shoppingList
     *
     * @return array
     */
    public function getTotalsQuantityPrice(array $products, ShoppingList $shoppingList = null): array
    {
        $totals = [];
        $configurableProducts = [];
        foreach ($products as $product) {
            if ($product->get('type') === Product::TYPE_CONFIGURABLE) {
                $configurableProducts[] = $product;
            }
        }
        if ($configurableProducts) {
            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManagerForClass(Product::class);
            foreach ($configurableProducts as $product) {
                $productId = $product->getId();
                $productEntity = $em->getReference(Product::class, $productId);
                $totals[$productId] = [
                    'quantity' => $this->getTotalQuantity($productEntity, $shoppingList),
                    'price' => $this->getTotalPriceFormatted($productEntity, $shoppingList),
                ];
            }
        }

        return $totals;
    }
}
