<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Adds to LineItemDataBuildEvent the data needed for shopping list edit page.
 */
class LineItemDataBuildViolationsListener
{
    /** @var LineItemViolationsProvider */
    private $violationsProvider;

    /**
     * @param LineItemViolationsProvider $violationsProvider
     */
    public function __construct(LineItemViolationsProvider $violationsProvider)
    {
        $this->violationsProvider = $violationsProvider;
    }

    /**
     * @param LineItemDataBuildEvent $event
     */
    public function onLineItemData(LineItemDataBuildEvent $event): void
    {
        $lineItems = $event->getLineItems();
        $errors = $this->violationsProvider->getLineItemViolationLists($lineItems);
        if (!$errors) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            $event->addDataForLineItem(
                $lineItem->getId(),
                'errors',
                $this->getErrors($errors, $lineItem->getProductSku(), $lineItem->getProductUnitCode())
            );
        }
    }

    /**
     * @param array $errors
     * @param string $sku
     * @param string $unit
     * @return array
     */
    private function getErrors(array $errors, string $sku, string $unit): array
    {
        return array_map(
            static fn (ConstraintViolationInterface $error) => $error->getMessage(),
            $errors[sprintf('product.%s.%s', $sku, $unit)] ?? []
        );
    }
}
