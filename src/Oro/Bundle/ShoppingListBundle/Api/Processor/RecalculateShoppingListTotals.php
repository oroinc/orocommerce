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

    public function __construct(
        private readonly ShoppingListTotalManager $totalManager
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $entity = $context->getData();
        if ($entity instanceof ShoppingList) {
            $this->recalculateTotals($entity, $context, false);
        } elseif ($entity instanceof LineItem) {
            $shoppingList = $entity->getShoppingList();
            if (null !== $shoppingList) {
                $this->recalculateTotals($shoppingList, $context, true);
            }
        } elseif ($entity instanceof ProductKitItemLineItem) {
            $shoppingList = $entity->getLineItem()?->getShoppingList();
            if (null !== $shoppingList) {
                $this->recalculateTotals($shoppingList, $context, true);
            }
        }
    }

    private function recalculateTotals(
        ShoppingList $shoppingList,
        CustomizeFormDataContext $context,
        bool $forceLoadLineItems
    ): void {
        $sharedData = $context->getSharedData();
        $processedShoppingLists = $sharedData->get(self::PROCESSED_SHOPPING_LISTS) ?? [];
        $shoppingListHash = spl_object_hash($shoppingList);
        if (!isset($processedShoppingLists[$shoppingListHash])) {
            if ($this->isTotalsRecalculationRequired($shoppingList, $context, $forceLoadLineItems)) {
                $this->totalManager->recalculateTotals($shoppingList, false);
            }
            $processedShoppingLists[$shoppingListHash] = true;
            $sharedData->set(self::PROCESSED_SHOPPING_LISTS, $processedShoppingLists);
        }
    }

    private function isTotalsRecalculationRequired(
        ShoppingList $shoppingList,
        CustomizeFormDataContext $context,
        bool $forceLoadLineItems
    ): bool {
        return
            $this->isEntityValid($shoppingList, $context)
            && $this->isLineItemsValid($shoppingList->getLineItems(), $context, $forceLoadLineItems);
    }

    private function isEntityValid(object $entity, CustomizeFormDataContext $context): bool
    {
        $form = $context->findForm($entity);

        return null === $form || $form->isValid();
    }

    private function isLineItemsValid(
        Collection $lineItems,
        CustomizeFormDataContext $context,
        bool $forceLoadLineItems
    ): bool {
        if (!$forceLoadLineItems && $lineItems instanceof PersistentCollection && !$lineItems->isInitialized()) {
            return false;
        }

        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if (!$this->isEntityValid($lineItem, $context)) {
                return false;
            }
            if (!$this->isKitItemLineItemsValid($lineItem->getKitItemLineItems(), $context)) {
                return false;
            }
        }

        return true;
    }

    private function isKitItemLineItemsValid(Collection $kitItemLineItems, CustomizeFormDataContext $context): bool
    {
        /** @var ProductKitItemLineItem $kitItemLineItem */
        foreach ($kitItemLineItems as $kitItemLineItem) {
            if (!$this->isEntityValid($kitItemLineItem, $context)) {
                return false;
            }
        }

        return true;
    }
}
