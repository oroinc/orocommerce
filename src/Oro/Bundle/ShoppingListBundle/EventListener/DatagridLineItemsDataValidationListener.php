<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ShoppingListBundle\Model\Factory\ShoppingListLineItemsHolderFactory;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates line items and adds warnings and errors to line items data.
 */
class DatagridLineItemsDataValidationListener
{
    public const ERRORS = 'errors';
    public const WARNINGS = 'warnings';
    public const KIT_HAS_GENERAL_ERROR = 'kitHasGeneralError';

    private ValidatorInterface $validator;

    private ShoppingListLineItemsHolderFactory $lineItemsHolderFactory;

    private TranslatorInterface $translator;

    private array $validationGroups = ['Default', 'datagrid_line_items_data'];

    public function __construct(
        ValidatorInterface $validator,
        ShoppingListLineItemsHolderFactory $lineItemsHolderFactory,
        TranslatorInterface $translator
    ) {
        $this->validator = $validator;
        $this->lineItemsHolderFactory = $lineItemsHolderFactory;
        $this->translator = $translator;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $violationList = $this->validator->validate(
            $this->lineItemsHolderFactory->createFromLineItems($lineItems),
            null,
            $this->validationGroups
        );

        [$warnings, $errors] = $this->getMessages($violationList);
        foreach ($lineItems as $index => $lineItem) {
            $lineItemId = $lineItem->getEntityIdentifier();

            $lineItemData = $event->getDataForLineItem($lineItemId);
            $lineItemData[self::WARNINGS] = $warnings[$index] ?? [];
            $lineItemData[self::ERRORS] = $errors[$index] ?? [];

            if ($lineItemData[DatagridKitLineItemsDataListener::IS_KIT] ?? false) {
                $lineItemData[self::KIT_HAS_GENERAL_ERROR] = $lineItemData[self::KIT_HAS_GENERAL_ERROR] ?? false;
                foreach ($lineItemData[DatagridKitLineItemsDataListener::SUB_DATA] as $kitItemLineItemData) {
                    if (!empty($kitItemLineItemData[self::ERRORS])) {
                        $lineItemData[self::KIT_HAS_GENERAL_ERROR] = true;
                        break;
                    }
                }

                if ($lineItemData[self::KIT_HAS_GENERAL_ERROR]) {
                    $lineItemData[self::ERRORS][] = $this->translator
                        ->trans('oro.shoppinglist.product_kit_line_item.general_error.message', [], 'validators');
                }
            }

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    /**
     * @param ConstraintViolationListInterface $violationList
     *
     * @return array{array<int,string[]>,array<int,string[]>}
     */
    private function getMessages(ConstraintViolationListInterface $violationList): array
    {
        $warnings = [];
        $errors = [];

        foreach ($violationList as $violation) {
            $propertyPath = new PropertyPath($violation->getPropertyPath());
            $elements = $propertyPath->getElements();
            if (array_shift($elements) !== 'lineItems') {
                continue;
            }

            $index = array_shift($elements);
            if ($index === null) {
                continue;
            }

            $severity = $violation->getConstraint()->payload['severity'] ?? 'error';
            if ($severity === 'warning') {
                $warnings[$index][] = $violation->getMessage();
            } else {
                $errors[$index][] = $violation->getMessage();
            }
        }

        return [$warnings, $errors];
    }
}
