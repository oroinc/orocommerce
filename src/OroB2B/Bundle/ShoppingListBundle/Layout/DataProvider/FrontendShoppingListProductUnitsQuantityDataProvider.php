<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;

class FrontendShoppingListProductUnitsQuantityDataProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var ProductShoppingListsDataProvider
     */
    protected $productShoppingListsDataProvider;

    /**
     * @param ProductShoppingListsDataProvider $productShoppingListsDataProvider
     */
    public function __construct(ProductShoppingListsDataProvider $productShoppingListsDataProvider)
    {
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $product = $context->data()->get('product');

        if (null === $product) {
            return null;
        }

        return $this->productShoppingListsDataProvider->getProductUnitsQuantity($product);
    }
}
