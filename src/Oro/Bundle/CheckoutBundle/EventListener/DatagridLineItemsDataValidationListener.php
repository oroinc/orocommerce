<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupsProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates line items and adds warnings and errors to line items data.
 */
class DatagridLineItemsDataValidationListener
{
    public const KIT_HAS_GENERAL_ERROR = 'kitHasGeneralError';

    public function __construct(
        private readonly InvalidShoppingListLineItemsProvider $invalidShoppingListLineItemsProvider,
        private readonly ProductLineItemsHolderFactoryInterface $lineItemsHolderFactory,
        private readonly TranslatorInterface $translator,
        private readonly ShoppingListValidationGroupsProvider $validationGroupsProvider
    ) {
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        if (!$this->isContinueActionAvailable()) {
            return;
        }

        $indexToIdMap = $this->createIndexToIdMap($lineItems);
        $idToIndexMap = array_flip($indexToIdMap);

        $violations = $this->invalidShoppingListLineItemsProvider->getInvalidItemsViolations(
            $lineItems,
            $event->getDatagrid()->getParameters()?->get('triggered_by')
        );

        [$warnings, $errors, $kitItemWarnings, $kitItemErrors] = $this->convertViolationsToMessages(
            $violations,
            $idToIndexMap,
            $lineItems
        );

        foreach ($lineItems as $lineItemId => $lineItem) {
            $numericIndex = $idToIndexMap[$lineItemId] ?? null;
            if ($numericIndex === null) {
                continue;
            }

            $lineItemData = $event->getDataForLineItem($lineItemId);
            $this->addLineItemMessages($lineItemData, $warnings, $errors, $numericIndex);

            if ($lineItemData[DatagridKitLineItemsDataListener::IS_KIT] ?? false) {
                $this->processKitItemMessages(
                    $lineItemData,
                    $kitItemWarnings,
                    $kitItemErrors,
                    $numericIndex
                );
            }

            $lineItemData[ShoppingListInvalidLineItemsMapper::WARNINGS] = array_unique(
                $lineItemData[ShoppingListInvalidLineItemsMapper::WARNINGS] ?? []
            );
            $lineItemData[ShoppingListInvalidLineItemsMapper::ERRORS] = array_unique(
                $lineItemData[ShoppingListInvalidLineItemsMapper::ERRORS] ?? []
            );

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    /**
     * Converts format from getInvalidItemsViolations() (by ID) to listener format (by numeric indices)
     *
     * @param array $violations Result from getInvalidItemsViolations()
     * @param array<int|string, int> $idToIndexMap Mapping ID => numeric index
     * @param array<int|string, ProductLineItemInterface> $lineItems Line items for getting kit item indices
     *
     * @return array{
     *     array<int|string,string[]>,
     *     array<int|string,string[]>,
     *     array<int|string,array<int|string,string[]>>,
     *     array<int|string,array<int|string,string[]>>
     * }
     */
    private function convertViolationsToMessages(
        array $violations,
        array $idToIndexMap,
        array $lineItems
    ): array {
        $kitItemIdToIndexMap = $this->createKitItemIdToIndexMap($lineItems, $idToIndexMap);

        [$warnings, $kitItemWarnings] = $this->processViolationsBySeverity(
            $violations,
            ShoppingListInvalidLineItemsMapper::WARNINGS,
            $idToIndexMap,
            $kitItemIdToIndexMap
        );

        [$errors, $kitItemErrors] = $this->processViolationsBySeverity(
            $violations,
            ShoppingListInvalidLineItemsMapper::ERRORS,
            $idToIndexMap,
            $kitItemIdToIndexMap
        );

        return [$warnings, $errors, $kitItemWarnings, $kitItemErrors];
    }

    /**
     * Processes violations by severity (errors or warnings)
     *
     * @param array $violations Result from getInvalidItemsViolations()
     * @param string $severity ShoppingListInvalidLineItemsMapper::ERRORS
     * or ShoppingListInvalidLineItemsMapper::WARNINGS
     * @param array<int|string, int> $idToIndexMap Mapping ID => numeric index
     * @param array<int, array<int, int>> $kitItemIdToIndexMap Mapping kit item ID => numeric index
     *
     * @return array{array<int|string,string[]>, array<int|string,array<int|string,string[]>>}
     */
    private function processViolationsBySeverity(
        array $violations,
        string $severity,
        array $idToIndexMap,
        array $kitItemIdToIndexMap
    ): array {
        $messages = [];
        $kitItemMessages = [];

        foreach ($violations[$severity] ?? [] as $lineItemId => $lineItemViolations) {
            $numericIndex = $idToIndexMap[$lineItemId] ?? null;
            if ($numericIndex === null) {
                continue;
            }

            foreach ($lineItemViolations[ShoppingListInvalidLineItemsMapper::MESSAGES] ?? [] as $violation) {
                $messages[$numericIndex][] = $violation->getMessage();
            }

            $subDataKey = ShoppingListInvalidLineItemsMapper::SUB_DATA;

            foreach ($lineItemViolations[$subDataKey] ?? [] as $kitItemId => $kitItemViolations) {
                $kitItemNumericIndex = $kitItemIdToIndexMap[$lineItemId][$kitItemId] ?? null;
                if ($kitItemNumericIndex === null) {
                    continue;
                }

                foreach ($kitItemViolations[ShoppingListInvalidLineItemsMapper::MESSAGES] ?? [] as $violation) {
                    $kitItemMessages[$numericIndex][$kitItemNumericIndex][] = $violation->getMessage();
                }
            }
        }

        return [$messages, $kitItemMessages];
    }

    /**
     * Creates mapping kit item ID => numeric index for each line item
     *
     * @param array<int|string, ProductLineItemInterface> $lineItems
     * @param array<int|string, int> $idToIndexMap
     * @return array<int, array<int, int>> [lineItemId => [kitItemId => numericIndex]]
     */
    private function createKitItemIdToIndexMap(array $lineItems, array $idToIndexMap): array
    {
        $kitItemIdToIndexMap = [];
        $lineItemsHolder = $this->lineItemsHolderFactory->createFromLineItems($lineItems);
        $lineItemsCollection = $lineItemsHolder->getLineItems();

        foreach ($lineItemsCollection as $lineItemNumericIndex => $lineItem) {
            $lineItemId = $lineItem->getEntityIdentifier();
            if (!isset($idToIndexMap[$lineItemId])) {
                continue;
            }

            if (!$lineItem instanceof ProductKitItemLineItemsAwareInterface) {
                continue;
            }

            $kitItemLineItems = $lineItem->getKitItemLineItems();
            if (!$kitItemLineItems) {
                continue;
            }

            $kitItemIdToIndexMap[$lineItemId] = [];
            foreach ($kitItemLineItems as $kitItemNumericIndex => $kitItemLineItem) {
                if (!$kitItemLineItem instanceof ProductLineItemInterface) {
                    continue;
                }
                $kitItemId = $kitItemLineItem->getEntityIdentifier();
                $kitItemIdToIndexMap[$lineItemId][$kitItemId] = $kitItemNumericIndex;
            }
        }

        return $kitItemIdToIndexMap;
    }

    /**
     * Create a map between numeric indices from property path and line item IDs.
     * Property path uses numeric indices based on the order in the collection.
     *
     * @param array<int|string,ProductLineItemInterface> $lineItems
     * @return array<int,int|string>
     */
    private function createIndexToIdMap(array $lineItems): array
    {
        $lineItemsHolder = $this->lineItemsHolderFactory->createFromLineItems($lineItems);
        $lineItemsCollection = $lineItemsHolder->getLineItems();
        $indexToIdMap = []; // [0 => id1, 1 => id2, ...]
        foreach ($lineItemsCollection as $numericIndex => $lineItem) {
            $indexToIdMap[$numericIndex] = $lineItem->getEntityIdentifier();
        }

        return $indexToIdMap;
    }

    /**
     * @param array<string,mixed> $lineItemData
     * @param array<int|string,string[]> $warnings
     * @param array<int|string,string[]> $errors
     * @param int|string $numericIndex
     */
    private function addLineItemMessages(
        array &$lineItemData,
        array $warnings,
        array $errors,
        int|string $numericIndex
    ): void {
        $lineItemData[ShoppingListInvalidLineItemsMapper::WARNINGS] = array_merge(
            $lineItemData[ShoppingListInvalidLineItemsMapper::WARNINGS] ?? [],
            $warnings[$numericIndex] ?? []
        );
        $lineItemData[ShoppingListInvalidLineItemsMapper::ERRORS] = array_merge(
            $lineItemData[ShoppingListInvalidLineItemsMapper::ERRORS] ?? [],
            $errors[$numericIndex] ?? []
        );
    }

    /**
     * @param array<string,mixed> $lineItemData
     * @param array<int|string,array<int|string,string[]>> $kitItemWarnings
     * @param array<int|string,array<int|string,string[]>> $kitItemErrors
     * @param int|string $numericIndex
     */
    private function processKitItemMessages(
        array &$lineItemData,
        array $kitItemWarnings,
        array $kitItemErrors,
        int|string $numericIndex
    ): void {
        $lineItemData[self::KIT_HAS_GENERAL_ERROR] = $lineItemData[self::KIT_HAS_GENERAL_ERROR] ?? false;

        $subData = $lineItemData[DatagridKitLineItemsDataListener::SUB_DATA] ?? [];
        foreach ($subData as $kitItemIndex => $kitItemLineItemData) {
            $this->addKitItemMessages(
                $subData[$kitItemIndex],
                $kitItemWarnings[$numericIndex][$kitItemIndex] ?? [],
                $kitItemErrors[$numericIndex][$kitItemIndex] ?? []
            );

            if (
                !empty($subData[$kitItemIndex][ShoppingListInvalidLineItemsMapper::ERRORS]) ||
                !empty($subData[$kitItemIndex][ShoppingListInvalidLineItemsMapper::WARNINGS])
            ) {
                $lineItemData[self::KIT_HAS_GENERAL_ERROR] = true;
            }
        }
        $lineItemData[DatagridKitLineItemsDataListener::SUB_DATA] = $subData;

        if ($lineItemData[self::KIT_HAS_GENERAL_ERROR]) {
            $lineItemData[ShoppingListInvalidLineItemsMapper::WARNINGS][] = $this->translator->trans(
                'oro.shoppinglist.product_kit_line_item.general_error.message',
                [],
                'validators'
            );
        }
    }

    /**
     * @param array<string,mixed> $kitItemData
     * @param array<string> $warnings
     * @param array<string> $errors
     */
    private function addKitItemMessages(
        array &$kitItemData,
        array $warnings,
        array $errors
    ): void {
        $kitItemData[ShoppingListInvalidLineItemsMapper::WARNINGS] = array_merge(
            $kitItemData[ShoppingListInvalidLineItemsMapper::WARNINGS] ?? [],
            $warnings
        );
        $kitItemData[ShoppingListInvalidLineItemsMapper::ERRORS] = array_merge(
            $kitItemData[ShoppingListInvalidLineItemsMapper::ERRORS] ?? [],
            $errors
        );
    }

    private function isContinueActionAvailable(): bool
    {
        return !empty($this->validationGroupsProvider->getAllValidationGroups());
    }
}
