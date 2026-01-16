<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\DatagridLineItemsDataValidationListener;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Validates line items to add validation metadata to line items data.
 * Validation metadata is used to control line item inputs and actions availability.
 *
 * @see InvalidLineItemsActionsOnResultAfterListener
 */
class DatagridLineItemsDataValidationMetadataListener
{
    public function __construct(
        private readonly InvalidShoppingListLineItemsProvider $invalidShoppingListLineItemsProvider
    ) {
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $parameters = $event->getDatagrid()->getParameters();
        $violations = $this->invalidShoppingListLineItemsProvider
            ->getInvalidItemsViolations($lineItems, $parameters->get('triggered_by'));

        foreach ($lineItems as $lineItem) {
            $lineItemId = $lineItem->getEntityIdentifier();
            $lineItemData = $event->getDataForLineItem($lineItemId);

            /** @var ConstraintViolationInterface $violation */
            foreach ($this->iterateViolations($violations, $lineItemId) as $violation) {
                $propertyPath = new PropertyPath($violation->getPropertyPath());
                $propertyPathElements = $propertyPath->getElements();
                if (end($propertyPathElements) === 'quantity') {
                    $lineItemData['validationMetadata']['enableQuantityInput'] = true;
                }
            }

            if ($lineItemData['isKit'] ?? false) {
                $lineItemData['validationMetadata']['enableProductKitConfigure']
                    = $lineItemData[DatagridLineItemsDataValidationListener::KIT_HAS_GENERAL_ERROR] ?? false;
            }

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    private function iterateViolations(array $violations, int $lineItemId): \Generator
    {
        $errors = &$violations[ShoppingListInvalidLineItemsMapper::ERRORS][$lineItemId];
        $warnings = &$violations[ShoppingListInvalidLineItemsMapper::WARNINGS][$lineItemId];

        foreach ($errors[ShoppingListInvalidLineItemsMapper::MESSAGES] ?? [] as $violation) {
            yield $violation;
        }

        foreach ($warnings[ShoppingListInvalidLineItemsMapper::MESSAGES] ?? [] as $violation) {
            yield $violation;
        }
    }
}
