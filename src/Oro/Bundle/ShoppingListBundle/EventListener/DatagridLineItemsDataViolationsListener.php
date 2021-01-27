<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Adds line items violations data.
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
        $violations = $this->violationsProvider->getLineItemViolationLists(
            $lineItems,
            $this->getAdditionalContext($event)
        );
        if (!$violations) {
            return;
        }

        foreach ($lineItems as $lineItem) {
            [$warnings, $errors] = $this->getMessages(
                $violations,
                $lineItem->getProductSku(),
                $lineItem->getProductUnitCode()
            );

            $event->addDataForLineItem($lineItem->getId(), ['warnings' => $warnings, 'errors' => $errors]);
        }
    }

    /**
     * @return object|null
     */
    protected function getAdditionalContext(DatagridLineItemsDataEvent $event): ?object
    {
        return null;
    }

    /**
     * @param array $violations
     * @param string $sku
     * @param string $unit
     * @return array
     */
    private function getMessages(array $violations, string $sku, string $unit): array
    {
        $warnings = [];
        $errors = [];

        /** @var ConstraintViolation $violation */
        foreach ($violations[sprintf('product.%s.%s', $sku, $unit)] ?? [] as $violation) {
            if ($violation->getCause() === 'warning') {
                $warnings[] = $violation->getMessage();
            } else {
                $errors[] = $violation->getMessage();
            }
        }

        return [$warnings, $errors];
    }
}
