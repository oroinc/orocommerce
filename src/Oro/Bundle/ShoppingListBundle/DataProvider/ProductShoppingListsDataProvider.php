<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;

/**
 * Provides info about shopping lists contains a specific products.
 */
class ProductShoppingListsDataProvider
{
    /** @var CurrentShoppingListManager */
    protected $currentShoppingListManager;

    /** @var LineItemRepository */
    protected $lineItemRepository;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var ConfigManager */
    protected $configManager;

    /** @var boolean|null */
    private $isShowAllShoppingLists;

    public function __construct(
        CurrentShoppingListManager $currentShoppingListManager,
        LineItemRepository $lineItemRepository,
        AclHelper $aclHelper,
        TokenAccessorInterface $tokenAccessor,
        ConfigManager $configManager
    ) {
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->lineItemRepository = $lineItemRepository;
        $this->aclHelper = $aclHelper;
        $this->tokenAccessor = $tokenAccessor;
        $this->configManager = $configManager;
    }

    /**
     * @param Product $product
     *
     * @return array|null
     */
    public function getProductUnitsQuantity(Product $product)
    {
        $shoppingLists = $this->getProductsUnitsQuantity([$product]);

        return $shoppingLists[$product->getId()] ?? null;
    }

    /**
     * @param Product[] $products
     *
     * @return array
     */
    public function getProductsUnitsQuantity(array $products)
    {
        $currentShoppingList = $this->currentShoppingListManager->getCurrent();

        if (!$currentShoppingList) {
            return [];
        }

        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            $shoppingLists = $this->prepareShoppingListsForGuestUser($currentShoppingList);
        } else {
            $shoppingLists = $this->prepareShoppingLists($products);
        }

        $shoppingLists = $this->sortShoppingLists($shoppingLists, $currentShoppingList);

        return $shoppingLists;
    }

    /**
     * @param ShoppingList $currentShoppingList
     *
     * @return array
     */
    private function prepareShoppingListsForGuestUser(ShoppingList $currentShoppingList)
    {
        $lineItems = $currentShoppingList->getLineItems()->toArray();

        return $this->prepareShoppingListsData($lineItems);
    }

    /**
     * @param Product[] $products
     *
     * @return array
     */
    private function prepareShoppingLists(array $products)
    {
        $lineItems = $this->lineItemRepository->getProductItemsWithShoppingListNames(
            $this->aclHelper,
            $products,
            $this->isShowAllInShoppingListWidget() ? null : $this->tokenAccessor->getUser()
        );

        return $this->prepareShoppingListsData($lineItems);
    }

    private function isShowAllInShoppingListWidget(): bool
    {
        if ($this->isShowAllShoppingLists === null) {
            $this->isShowAllShoppingLists = (bool)$this->configManager
                ->get('oro_shopping_list.show_all_in_shopping_list_widget');
        }

        return $this->isShowAllShoppingLists;
    }

    /**
     * @param LineItem[] $lineItems
     *
     * @return array
     */
    private function prepareShoppingListsData(array $lineItems)
    {
        $shoppingLists = [];

        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();

            if ($lineItem->getParentProduct()) {
                $parentProduct = $lineItem->getParentProduct();
                $productShoppingLists = $this->saveShoppingListData($parentProduct->getId(), $lineItem, $shoppingLists);
                $shoppingLists[$parentProduct->getId()] = $productShoppingLists;
            }

            $productShoppingLists = $this->saveShoppingListData($product->getId(), $lineItem, $shoppingLists);
            $shoppingLists[$product->getId()] = $productShoppingLists;
        }

        return $shoppingLists;
    }

    /**
     * @param int $productId
     * @param LineItem $lineItem
     * @param array $shoppingLists
     * @return array
     */
    private function saveShoppingListData($productId, LineItem $lineItem, array $shoppingLists)
    {
        $shoppingList = $lineItem->getShoppingList();
        $shoppingListId = $shoppingList->getId();

        $productShoppingLists = $this->getProductShoppingList($shoppingLists, $productId);

        if (!isset($productShoppingLists[$shoppingListId])) {
            $productShoppingLists[$shoppingListId] = [
                'id' => $shoppingListId,
                'label' => $shoppingList->getLabel(),
                'is_current' => $shoppingList->isCurrent(),
                'line_items' => []
            ];
        }

        $productShoppingLists[$shoppingListId]['line_items'][] = [
            'id' => $lineItem->getId(),
            'productId' => $lineItem->getProduct()->getId(),
            'unit' => $lineItem->getProductUnitCode(),
            'quantity' => $lineItem->getQuantity()
        ];

        return $productShoppingLists;
    }

    /**
     * @param array $shoppingLists
     * @param ShoppingList $currentShoppingList
     *
     * @return array
     */
    private function sortShoppingLists(array $shoppingLists, ShoppingList $currentShoppingList)
    {
        $sortedShoppingLists = [];
        $currentShoppingListId = $currentShoppingList->getId();

        foreach ($shoppingLists as $productId => $productShoppingLists) {
            if (isset($productShoppingLists[$currentShoppingListId])) {
                $currentShoppingList = $productShoppingLists[$currentShoppingListId];
                unset($productShoppingLists[$currentShoppingListId]);
                array_unshift($productShoppingLists, $currentShoppingList);
            }

            $sortedShoppingLists[$productId] = array_values($productShoppingLists);
        }

        return $sortedShoppingLists;
    }

    /**
     * @param array $shoppingLists
     * @param int $productId
     *
     * @return array
     */
    private function getProductShoppingList(array $shoppingLists, $productId)
    {
        return $shoppingLists[$productId] ?? [];
    }
}
