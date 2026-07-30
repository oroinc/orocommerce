<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\LineItem\Factory;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Model\LineItemModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Builds the list of {@see LineItemModel} for the shopping list line items batch update from the raw request data.
 *
 * Ensures the current user is allowed to edit each referenced line item and that the line item belongs to the
 * updated shopping list, so line items can neither be modified nor re-assigned to (and re-owned by) another
 * customer user, including via a guest shopping list.
 */
class BatchUpdateLineItemModelsFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenAccessorInterface $tokenAccessor
    ) {
        $this->logger = new NullLogger();
    }

    /**
     * @param array<array<string,int|float|string>> $rawLineItems
     *
     * @return array<LineItemModel>
     */
    public function createLineItemModels(array $rawLineItems, ShoppingList $shoppingList): array
    {
        $lineItemRepository = $this->doctrine->getRepository(LineItem::class);
        assert($lineItemRepository instanceof LineItemRepository);

        $rawLineItemsById = $this->indexRawLineItemsById($rawLineItems);
        if (!$rawLineItemsById) {
            return [];
        }

        /** @var array<LineItem> $lineItems */
        $lineItems = $lineItemRepository->findBy(['id' => array_keys($rawLineItemsById)]);
        /** @var array<LineItemModel> $lineItemModels */
        $lineItemModels = [];
        /** @var array<int> $deniedLineItemIds */
        $deniedLineItemIds = [];
        foreach ($lineItems as $lineItem) {
            $rawLineItem = $rawLineItemsById[$lineItem->getId()] ?? null;
            if ($rawLineItem === null) {
                continue;
            }

            if (!$this->isLineItemEditable($lineItem, $shoppingList)) {
                $deniedLineItemIds[] = $lineItem->getId();

                continue;
            }

            $lineItemModel = $this->createLineItemModel($lineItem, $rawLineItem);
            if ($lineItemModel !== null) {
                $lineItemModels[] = $lineItemModel;
            }
        }

        if ($deniedLineItemIds) {
            $this->logAccessDenied($deniedLineItemIds, $shoppingList);
        }

        return $lineItemModels;
    }

    /**
     * @param array<array<string,int|float|string>> $rawLineItems
     *
     * @return array<int,array<string,int|float|string>>
     */
    private function indexRawLineItemsById(array $rawLineItems): array
    {
        $rawLineItemsById = [];
        foreach ($rawLineItems as $rawLineItem) {
            $id = filter_var($rawLineItem['id'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if ($id) {
                $rawLineItemsById[$id] = $rawLineItem;
            }
        }

        return $rawLineItemsById;
    }

    private function isLineItemEditable(LineItem $lineItem, ShoppingList $shoppingList): bool
    {
        // The line item must belong to the updated shopping list.
        return $lineItem->getAssociatedList()?->getId() === $shoppingList->getId()
            && $this->authorizationChecker->isGranted('EDIT', $lineItem)
            && $this->authorizationChecker->isGranted('EDIT', $lineItem->getAssociatedList());
    }

    /**
     * @param array<int> $deniedLineItemIds
     */
    private function logAccessDenied(array $deniedLineItemIds, ShoppingList $shoppingList): void
    {
        $customerUser = $this->tokenAccessor->getUser();
        $this->logger->error(
            'Access to edit shopping list line items during a batch update was denied. '
            . 'This might be an attempt to modify line items that belong to another customer user '
            . 'or to another shopping list.',
            [
                'lineItemIds' => $deniedLineItemIds,
                'shoppingListId' => $shoppingList->getId(),
                'customerUserId' => $customerUser instanceof CustomerUser ? $customerUser->getId() : null,
            ]
        );
    }

    /**
     * @param array<string,int|float|string> $rawLineItem
     */
    private function createLineItemModel(LineItem $lineItem, array $rawLineItem): ?LineItemModel
    {
        $quantity = filter_var($rawLineItem['quantity'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
        $unitCode = filter_var($rawLineItem['unitCode'] ?? null, FILTER_DEFAULT, FILTER_NULL_ON_FAILURE);
        if ($quantity === null || $quantity <= 0 || $unitCode === null) {
            return null;
        }

        return new LineItemModel($lineItem->getId(), (float)$quantity, (string)$unitCode);
    }
}
