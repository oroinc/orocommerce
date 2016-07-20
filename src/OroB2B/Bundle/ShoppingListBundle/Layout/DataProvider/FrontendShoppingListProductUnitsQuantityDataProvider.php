<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendShoppingListProductUnitsQuantityDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var LineItemRepository
     */
    protected $lineItemRepository;

    /**
     * @param ShoppingListManager $shoppingListManager
     * @param LineItemRepository $lineItemRepository
     */
    public function __construct(ShoppingListManager $shoppingListManager, LineItemRepository $lineItemRepository)
    {
        $this->shoppingListManager = $shoppingListManager;
        $this->lineItemRepository = $lineItemRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Product $product */
        $product = $context->data()->get('product');
        if (!$product) {
            return null;
        }

        $this->setProductsShoppingLists([$product]);

        return $this->data[$product->getId()];
    }

    public function getProductsShoppingLists($products)
    {
        $this->setProductsShoppingLists($products);
        $productsUnits = [];

        foreach ($products as $product) {
            $productId = $product->getId();
            if ($this->data[$productId]) {
                $productsUnits[$productId] = $this->data[$productId];
            }
        }

        return $productsUnits;
    }

    protected function setProductsShoppingLists($products)
    {
        $products = array_filter($products, function ($product) {
            return !array_key_exists($product->getId(), $this->data);
        });
        if (!$products) {
            return;
        }

        $shoppingList = $this->shoppingListManager->getCurrent();
        if (!$shoppingList) {
            return;
        }

        $items = $this->lineItemRepository->getItemsByShoppingListAndProducts($shoppingList, $products);
        $productsUnits = [];

        foreach ($items as $item) {
            $productsUnits[$item->getProduct()->getId()][$item->getProductUnitCode()] = $item->getQuantity();
        }

        foreach ($products as $product) {
            $productId = $product->getId();
            $this->data[$productId] = isset($productsUnits[$productId]) ? $productsUnits[$productId] : [];
        }
    }
}
