<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Mapper;

use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Creates a violations map of invalid shopping list line items by severity.
 */
class ShoppingListInvalidLineItemsMapper
{
    public const string ERRORS = 'errors';
    public const string WARNINGS = 'warnings';
    public const string MESSAGES = 'messages';
    public const string SUB_DATA = 'subData';
    private const string GROUP_CONSTRAINTS_KEY = 'shopping_list_group_key';

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @return array{
     *     errors: array<int, array{
     *         messages: list<ConstraintViolationInterface>,
     *         subData: array<int, array{
     *             messages: list<ConstraintViolationInterface>
     *         }>
     *     }>,
     *     warnings: array<int, array{
     *         messages: list<ConstraintViolationInterface>,
     *         subData: array<int, array{
     *             messages: list<ConstraintViolationInterface>
     *         }>
     *     }>
     * }
     */
    public function mapViolationListBySeverity(
        ConstraintViolationListInterface $violationList,
        array $validationGroups
    ): array {
        if (empty($validationGroups)) {
            throw new \InvalidArgumentException('Validation groups are required');
        }

        $result = [self::ERRORS => [], self::WARNINGS => []];
        $groupedViolations = $this->groupViolationsByConstraint($violationList);

        foreach ($groupedViolations as $violationsGroup) {
            foreach ($violationsGroup as $violation) {
                if (!\str_contains($violation->getPropertyPath(), 'lineItems[')) {
                    continue;
                }

                $lineItem = $this->getLineItemFromViolation($violation);
                if ($lineItem === null) {
                    continue;
                }

                $parentId = $this->getParentLineItemId($lineItem);
                if ($parentId === null) {
                    continue;
                }

                $severity = $this->isError($violationsGroup, $validationGroups) ? self::ERRORS : self::WARNINGS;
                if (!isset($result[$severity][$parentId])) {
                    $result[$severity][$parentId] = [self::MESSAGES => [], self::SUB_DATA => []];
                }

                if ($lineItem instanceof ProductKitItemLineItemInterface) {
                    $result[$severity][$parentId][self::SUB_DATA][$lineItem->getId()][self::MESSAGES][] = $violation;
                    continue;
                }

                $result[$severity][$parentId][self::MESSAGES][] = $violation;
            }
        }

        return $result;
    }

    public function groupViolationsByConstraint(ConstraintViolationListInterface $violations): array
    {
        $result = [];

        foreach ($violations as $violation) {
            $groupKey = $this->getGroupConstraintKey($violation);

            if ($groupKey) {
                $result[$groupKey][] = $violation;
                continue;
            }

            $result[][] = $violation;
        }

        return $result;
    }

    private function getGroupConstraintKey(ConstraintViolation $violation): ?string
    {
        $constraint = $violation->getConstraint();

        if (!$constraint) {
            return null;
        }

        return $constraint->payload[self::GROUP_CONSTRAINTS_KEY] ?? null;
    }

    public function isError(array $violationsGroup, array $validationGroups): bool
    {
        if (empty($validationGroups)) {
            return true;
        }

        $violatedValidationGroups = $this->extractViolatedGroups($violationsGroup);

        return array_all($validationGroups, fn ($group) => in_array($group, $violatedValidationGroups, true));
    }

    private function extractViolatedGroups(array $violationsGroup): array
    {
        $violatedValidationGroups = [];

        /**
         * @var ConstraintViolation $violation
         */
        foreach ($violationsGroup as $violation) {
            $violatedValidationGroups = array_merge(
                $violatedValidationGroups,
                $violation->getConstraint()?->groups ?? []
            );
        }

        return $violatedValidationGroups;
    }

    private function getLineItemFromViolation(
        ConstraintViolationInterface $violation
    ): null|ProductLineItemInterface {
        $invalidValue = $violation->getInvalidValue();
        if ($invalidValue instanceof ProductLineItemInterface) {
            return $violation->getInvalidValue();
        }

        if (\preg_match_all('/\[(\d+)\]/', $violation->getPropertyPath(), $matches)) {
            $indexes = $matches[1];
            $lineItem = $violation->getRoot()?->getLineItems()?->get((int)$indexes[0]);

            if ($this->isKitItemViolation($violation)) {
                return $lineItem?->getKitItemLineItems()?->get((int)$indexes[1]);
            }

            return $lineItem;
        }

        return null;
    }

    private function isKitItemViolation(ConstraintViolationInterface $violation): bool
    {
        return \str_contains($violation->getPropertyPath(), '.kitItemLineItems[');
    }

    private function getParentLineItemId(ProductLineItemInterface $lineItem): ?int
    {
        if ($lineItem instanceof ProductKitItemLineItemInterface) {
            return  $lineItem->getLineItem()?->getId();
        }

        return $lineItem->getId();
    }
}
