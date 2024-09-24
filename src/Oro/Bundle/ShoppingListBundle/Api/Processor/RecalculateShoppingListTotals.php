<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Recalculates the shopping list totals.
 */
class RecalculateShoppingListTotals implements ProcessorInterface
{
    private const PROCESSED_SHOPPING_LISTS = 'recalculated_shopping_list_totals';

    private ShoppingListTotalManager $totalManager;

    public function __construct(ShoppingListTotalManager $totalManager)
    {
        $this->totalManager = $totalManager;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $entity = $context->getData();
        if ($entity instanceof ShoppingList) {
            $this->recalculateTotals($entity, $context);
        } elseif ($entity instanceof LineItem) {
            $shoppingList = $entity->getShoppingList();
            if (null !== $shoppingList) {
                $this->recalculateTotals($shoppingList, $context);
            }
        } elseif ($entity instanceof ProductKitItemLineItem) {
            $shoppingList = $entity->getLineItem()?->getShoppingList();
            if (null !== $shoppingList) {
                $this->recalculateTotals($shoppingList, $context);
            }
        }
    }

    private function recalculateTotals(
        ShoppingList $shoppingList,
        CustomizeFormDataContext $context
    ): void {
        $sharedData = $context->getSharedData();
        $processedShoppingLists = $sharedData->get(self::PROCESSED_SHOPPING_LISTS) ?? [];
        $shoppingListHash = spl_object_hash($shoppingList);
        if (!isset($processedShoppingLists[$shoppingListHash])) {
            if ($this->isTotalsRecalculationRequired($shoppingList, $context)) {
                $this->totalManager->recalculateTotals($shoppingList, false);
            }
            $processedShoppingLists[$shoppingListHash] = true;
            $sharedData->set(self::PROCESSED_SHOPPING_LISTS, $processedShoppingLists);
        }
    }

    private function isTotalsRecalculationRequired(
        ShoppingList $shoppingList,
        CustomizeFormDataContext $context
    ): bool {
        $form = $context->findForm($shoppingList);
        if (null !== $form && !$form->isValid()) {
            return false;
        }

        return $this->isLineItemsValid($shoppingList->getLineItems(), $context);
    }

    /**
     * @param Collection<LineItem>|LineItem[] $lineItems
     * @param CustomizeFormDataContext $context
     *
     * @return bool
     */
    private function isLineItemsValid(Collection|array $lineItems, CustomizeFormDataContext $context): bool
    {
        if ($lineItems instanceof PersistentCollection && !$lineItems->isInitialized()) {
            return false;
        }

        foreach ($lineItems as $lineItem) {
            $form = $context->findForm($lineItem);
            if (null !== $form && !$form->isValid()) {
                return false;
            }

            $kitItemLineItems = $lineItem->getKitItemLineItems();
            if ($this->isKitItemLineItemsValid($kitItemLineItems, $context) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Collection<ProductKitItemLineItem>|ProductKitItemLineItem[] $kitItemLineItems
     * @param CustomizeFormDataContext $context
     *
     * @return bool
     */
    private function isKitItemLineItemsValid(
        Collection|array $kitItemLineItems,
        CustomizeFormDataContext $context
    ): bool {
        if ($kitItemLineItems->count() === 0) {
            return true;
        }
        if ($kitItemLineItems instanceof PersistentCollection && !$kitItemLineItems->isInitialized()) {
            return false;
        }

        foreach ($kitItemLineItems as $kitItemLineItem) {
            $form = $context->findForm($kitItemLineItem);
            if (null !== $form && !$form->isValid()) {
                return false;
            }
        }

        return true;
    }
}
