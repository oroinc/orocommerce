<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adjust form data for saved for later matrix form
 */
class SavedForLaterMatrixFormListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ManagerRegistry $registry
    ) {
    }

    public function onFormDataSet(FormProcessEvent $event): void
    {
        if (!$this->isSavedForLaterGrid()) {
            return;
        }

        $data = $event->getData();
        if (!$data instanceof MatrixCollection) {
            return;
        }

        $shoppingList = $this->getShoppingList();
        if (!$shoppingList) {
            return;
        }

        $lineItems = $shoppingList->getSavedForLaterLineItems();
        if ($lineItems->isEmpty()) {
            return;
        }

        $this->handleSavedForLaterLineItems($lineItems, $data);
    }

    private function handleSavedForLaterLineItems(Collection $lineItems, MatrixCollection $data): void
    {
        foreach ($data->rows as $row) {
            foreach ($row->columns as $column) {
                foreach ($lineItems as $lineItem) {
                    if ($lineItem->getProductUnitCode() !== $data->unit->getCode() ||
                        $lineItem->getProduct()->getId() !== $column->product?->getId()
                    ) {
                        continue;
                    }

                    $column->quantity = $lineItem->getQuantity();
                }
            }
        }
    }

    private function getShoppingList(): ?ShoppingList
    {
        $shoppingListId = $this->requestStack->getMainRequest()?->get('shoppingListId', null);

        return $shoppingListId ? $this->registry->getRepository(ShoppingList::class)->find($shoppingListId) : null;
    }

    private function isSavedForLaterGrid(): bool
    {
        $isSavedForLaterGrid = $this->requestStack->getMainRequest()?->get('savedForLaterGrid', false);

        return \filter_var($isSavedForLaterGrid, FILTER_VALIDATE_BOOLEAN);
    }
}
