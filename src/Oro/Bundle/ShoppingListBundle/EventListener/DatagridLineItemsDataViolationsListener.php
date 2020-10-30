<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Adds line items errors data.
 */
class DatagridLineItemsDataViolationsListener
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
     * @param DatagridLineItemsDataEvent $event
     */
    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        $errors = $this->violationsProvider->getLineItemViolationLists($lineItems);
        if (!$errors) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            $event->addDataForLineItem(
                $lineItem->getId(),
                ['errors' => $this->getErrors($errors, $lineItem->getProductSku(), $lineItem->getProductUnitCode())]
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
