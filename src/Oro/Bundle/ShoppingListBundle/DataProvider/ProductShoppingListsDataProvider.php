<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
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
    private CurrentShoppingListManager $currentShoppingListManager;
    private LineItemRepository $lineItemRepository;
    private AclHelper $aclHelper;
    private TokenAccessorInterface $tokenAccessor;
    private ConfigManager $configManager;

    private ?bool $isShowAllShoppingLists = null;

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
     * @param int $productId
     *
     * @return array|null [shopping list data (array), ...]
     */
    public function getProductUnitsQuantity(int $productId): ?array
    {
        $shoppingLists = $this->getProductsUnitsQuantity([$productId]);

        return $shoppingLists[$productId] ?? null;
    }

    /**
     * @param int[] $productIds
     *
     * @return array [product id => [shopping list data (array), ...], ...]
     */
    public function getProductsUnitsQuantity(array $productIds): array
    {
        $currentShoppingList = $this->currentShoppingListManager->getCurrent();
        if (!$currentShoppingList) {
            return [];
        }

        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            $shoppingLists = $this->prepareShoppingListsForGuestUser($currentShoppingList);
        } else {
            $shoppingLists = $this->prepareShoppingLists($productIds);
        }

        return $this->sortShoppingLists($shoppingLists, $currentShoppingList);
    }

    /**
     * @param ShoppingList $currentShoppingList
     *
     * @return array [product id => [shopping list id => shopping list data (array), ...], ...]
     */
    private function prepareShoppingListsForGuestUser(ShoppingList $currentShoppingList): array
    {
        return $this->prepareShoppingListsData($currentShoppingList->getLineItems()->toArray());
    }

    /**
     * @param int[] $productIds
     *
     * @return array [product id => [shopping list id => shopping list data (array), ...], ...]
     */
    private function prepareShoppingLists(array $productIds): array
    {
        $lineItems = $this->lineItemRepository->getProductItemsWithShoppingListNames(
            $this->aclHelper,
            $productIds,
            $this->isShowAllInShoppingListWidget() ? null : $this->tokenAccessor->getUser()
        );

        return $this->prepareShoppingListsData($lineItems);
    }

    private function isShowAllInShoppingListWidget(): bool
    {
        if (null === $this->isShowAllShoppingLists) {
            $this->isShowAllShoppingLists = (bool)$this->configManager
                ->get('oro_shopping_list.show_all_in_shopping_list_widget');
        }

        return $this->isShowAllShoppingLists;
    }

    /**
     * @param LineItem[] $lineItems
     *
     * @return array [product id => [shopping list id => shopping list data (array), ...], ...]
     */
    private function prepareShoppingListsData(array $lineItems): array
    {
        $shoppingLists = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getParentProduct()) {
                $parentProductId = $lineItem->getParentProduct()->getId();
                $shoppingLists[$parentProductId] = $this->saveShoppingListData(
                    $parentProductId,
                    $lineItem,
                    $shoppingLists
                );
            }

            $productId = $lineItem->getProduct()->getId();
            $shoppingLists[$productId] = $this->saveShoppingListData(
                $productId,
                $lineItem,
                $shoppingLists
            );
        }

        return $shoppingLists;
    }

    /**
     * @param int      $productId
     * @param LineItem $lineItem
     * @param array    $shoppingLists [product id => [shopping list id => shopping list data (array), ...], ...]
     *
     * @return array [shopping list id => shopping list data (array), ...]
     */
    private function saveShoppingListData(int $productId, LineItem $lineItem, array $shoppingLists): array
    {
        $shoppingList = $lineItem->getShoppingList();
        $shoppingListId = $shoppingList->getId();

        $productShoppingLists = $shoppingLists[$productId] ?? [];

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
     * @param array        $shoppingLists [product id => [shopping list id => shopping list data (array), ...], ...]
     * @param ShoppingList $currentShoppingList
     *
     * @return array [product id => [shopping list data (array), ...], ...]
     */
    private function sortShoppingLists(array $shoppingLists, ShoppingList $currentShoppingList): array
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
}
