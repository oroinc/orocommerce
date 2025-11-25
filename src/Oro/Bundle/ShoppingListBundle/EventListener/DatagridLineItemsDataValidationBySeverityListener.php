<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridKitLineItemsDataListener;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListValidationGroupsProvider;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates line items and adds warnings and errors to line items data by severity.
 */
class DatagridLineItemsDataValidationBySeverityListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;
    public const ERRORS = 'errors';
    public const WARNINGS = 'warnings';
    public const KIT_HAS_GENERAL_ERROR = 'kitHasGeneralError';

    private array $defaultValidationGroups = ['Default', 'datagrid_line_items_data'];

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ProductLineItemsHolderFactoryInterface $lineItemsHolderFactory,
        private readonly TranslatorInterface $translator,
        private readonly ShoppingListInvalidLineItemsMapper $mapper,
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

        $parameters = $event->getDatagrid()->getParameters();
        $groups = $this->getValidationGroups($parameters?->get('triggered_by'));

        $violationList = $this->validator->validate(
            $this->lineItemsHolderFactory->createFromLineItems($lineItems),
            null,
            $groups
        );

        [$warnings, $errors] = $this->getMessages($violationList, $groups);
        foreach ($lineItems as $index => $lineItem) {
            $lineItemId = $lineItem->getEntityIdentifier();

            $lineItemData = $event->getDataForLineItem($lineItemId);
            $lineItemData[self::WARNINGS] = array_merge($lineItemData[self::WARNINGS] ?? [], $warnings[$index] ?? []);
            $lineItemData[self::ERRORS] = array_merge($lineItemData[self::ERRORS] ?? [], $errors[$index] ?? []);

            if ($lineItemData[DatagridKitLineItemsDataListener::IS_KIT] ?? false) {
                $lineItemData[self::KIT_HAS_GENERAL_ERROR] = $lineItemData[self::KIT_HAS_GENERAL_ERROR] ?? false;
                foreach ($lineItemData[DatagridKitLineItemsDataListener::SUB_DATA] as $kitItemLineItemData) {
                    if (!empty($kitItemLineItemData[self::ERRORS]) || !empty($kitItemLineItemData[self::WARNINGS])) {
                        $lineItemData[self::KIT_HAS_GENERAL_ERROR] = true;
                        break;
                    }
                }

                if ($lineItemData[self::KIT_HAS_GENERAL_ERROR]) {
                    $lineItemData[self::WARNINGS][] = $this->translator
                        ->trans('oro.shoppinglist.product_kit_line_item.general_error.message', [], 'validators');
                }
            }

            $lineItemData[self::WARNINGS] = array_unique($lineItemData[self::WARNINGS]);
            $lineItemData[self::ERRORS] = array_unique($lineItemData[self::ERRORS]);

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    /**
     * @param ConstraintViolationListInterface $violationList
     *
     * @return array{array<int,string[]>,array<int,string[]>}
     */
    private function getMessages(ConstraintViolationListInterface $violationList, array $groups = []): array
    {
        $warnings = [];
        $errors = [];

        $groupedViolations = $this->mapper->groupViolationsByConstraint($violationList);

        foreach ($groupedViolations as $violationsGroup) {
            foreach ($violationsGroup as $violation) {
                // Because we loop through the listener twice(for line items and kit line items),
                // we don’t add it a second time for the kit items.
                if ($this->isKitItemViolation($violation)) {
                    continue;
                }

                $propertyPath = new PropertyPath($violation->getPropertyPath());
                $elements = $propertyPath->getElements();
                if (array_shift($elements) !== 'lineItems') {
                    continue;
                }

                $index = array_shift($elements);
                if ($index === null) {
                    continue;
                }

                $severity = $this->mapper->isError($violationsGroup, $groups) ? self::ERRORS : self::WARNINGS;
                if ($severity === self::ERRORS) {
                    $errors[$index][] = $violation->getMessage();
                } else {
                    $warnings[$index][] = $violation->getMessage();
                }
            }
        }

        return [$warnings, $errors];
    }

    /**
     * @return list<string>
     * @throws \InvalidArgumentException
     */
    private function getValidationGroups(?string $groupType = null): array
    {
        if (!$this->isFeaturesEnabled()) {
            return $this->defaultValidationGroups;
        }

        if (!$groupType) {
            return $this->validationGroupsProvider->getAllValidationGroups();
        }

        return [$this->validationGroupsProvider->getValidationGroupByType($groupType)];
    }

    private function isKitItemViolation(ConstraintViolationInterface $violation): bool
    {
        return \str_contains($violation->getPropertyPath(), '.kitItemLineItems[');
    }

    private function isContinueActionAvailable(): bool
    {
        return !empty($this->validationGroupsProvider->getAllValidationGroups());
    }
}
