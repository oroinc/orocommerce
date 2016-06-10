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
        $product = $context->data()->get('product');
        if (!$product) {
            return null;
        }

        $shoppingList = $this->shoppingListManager->getCurrent();
        if (!$shoppingList) {
            return null;
        }

        return $this->getProductUnitsQuantity($shoppingList, $product);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @return array
     */
    protected function getProductUnitsQuantity(ShoppingList $shoppingList, Product $product)
    {
        $items = $this->lineItemRepository->getItemsByShoppingListAndProduct($shoppingList, $product);
        $units = [];

        foreach ($items as $item) {
            $units[$item->getProductUnitCode()] = $item->getQuantity();
        }

        return $units;
    }
}
