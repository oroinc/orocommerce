<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Adds line items violations data.
 *
 * @deprecated since 5.1, use DatagridLineItemsDataValidationListener instead
 */
class DatagridLineItemsDataViolationsListener
{
    private LineItemViolationsProvider $violationsProvider;

    public function __construct(LineItemViolationsProvider $violationsProvider)
    {
        $this->violationsProvider = $violationsProvider;
    }

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
            $checksum = $lineItem instanceof ProductKitItemLineItemsAwareInterface ? $lineItem->getChecksum() : null;

            [$warnings, $errors] = $this->getMessages(
                $violations,
                $lineItem->getProductSku(),
                $lineItem->getProductUnitCode(),
                $checksum
            );

            $event->addDataForLineItem($lineItem->getId(), ['warnings' => $warnings, 'errors' => $errors]);
        }
    }

    protected function getAdditionalContext(DatagridLineItemsDataEvent $event): ?object
    {
        return null;
    }

    private function getMessages(array $violations, string $sku, string $unit, ?string $checksum): array
    {
        $warnings = [];
        $errors = [];
        $violationsPath = $this->createViolationPath($sku, $unit, $checksum);

        /** @var ConstraintViolation $violation */
        foreach ($violations[$violationsPath] ?? [] as $violation) {
            if ($violation->getCause() === 'warning') {
                $warnings[] = $violation->getMessage();
            } else {
                $errors[] = $violation->getMessage();
            }
        }

        return [$warnings, $errors];
    }

    private function createViolationPath(string $sku, string $unitCode, ?string $checksum): string
    {
        $path = sprintf('product.%s.%s', $sku, $unitCode);

        if (!empty($checksum)) {
            $path .= '.'. $checksum;
        }

        return $path;
    }
}
