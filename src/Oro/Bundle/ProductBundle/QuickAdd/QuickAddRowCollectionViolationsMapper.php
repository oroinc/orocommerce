<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\QuickAdd;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Maps constraint violation errors to each {@see QuickAddRow} of {@see QuickAddRowCollection}.
 */
class QuickAddRowCollectionViolationsMapper
{
    /**
     * Key used in constraint payload to group related constraints together.
     *
     * This allows different constraints to be treated as a single validation group.
     * When multiple constraints share the same group key, they are processed together:
     * - If ALL constraints in the group are violated → ERROR
     * - If SOME (but not all) constraints in the group are violated → WARNING
     *
     * Example: HasSupportedInventoryStatus constraint in OrderBundle validation.yml has
     * the same group key for both checkout and RFQ validation groups:
     * - Both violated (checkout + RFQ) → ERROR
     * - Only one violated (checkout OR RFQ) → WARNING
     *
     * Location: OrderBundle/Resources/config/validation.yml (QuickAddRow.product)
     */
    private const string CONSTRAINT_GROUP_KEY = 'constraint_group_key';

    private array $excludedGroupsFromMultiGroupValidation = [];

    /**
     * Sets groups that should be excluded from multi-group validation logic.
     *
     * This affects the ERROR vs WARNING determination when multiple validation groups are present:
     * - If only 1 validation group → excluded groups are ignored, violations always result in ERROR
     * - If multiple validation groups → excluded groups are removed from the calculation,
     *   requiring ALL remaining groups to be violated for ERROR (otherwise WARNING)
     *
     * Example use case for shopping list:
     * - Shopping list quick add processor is excluded from multi-group validation
     * - When validating Сheckout, RFQ, Shopping list groups: Only Checkout and RFQ violations = ERROR
     * - When validating only shopping list group: violations = ERROR
     *
     * @param array<string> $excludedGroupsFromMultiGroupValidation Array of group names to exclude from
     * multi-group logic
     */
    public function setExcludedGroupsFromMultiGroupValidation(
        array $excludedGroupsFromMultiGroupValidation
    ): void {
        $this->excludedGroupsFromMultiGroupValidation = $excludedGroupsFromMultiGroupValidation;
    }

    /**
     * @param iterable<ConstraintViolationInterface> $constraintViolations
     */
    public function mapViolations(
        QuickAddRowCollection $quickAddRowCollection,
        iterable $constraintViolations,
        bool $errorBubbling = false
    ): void {
        $this->mapViolationsAgainstGroups(
            $quickAddRowCollection,
            $constraintViolations,
            [],
            $errorBubbling
        );
    }

    /**
     * Maps constraint violations to QuickAdd rows based on validation groups.
     *
     * This method processes violations and determines whether they should be treated
     * as errors or warnings based on the validation groups logic:
     * - If ALL validation groups are violated → ERROR
     * - If SOME (but not all) validation groups are violated → WARNING
     * - If errorBubbling is true → all violations become collection-level errors
     *
     * @param QuickAddRowCollection $quickAddRowCollection The collection to map violations to
     * @param iterable<ConstraintViolation> $constraintViolations The violations to process
     * @param array<string> $groups Validation groups to determine error vs warning severity
     * @param bool $errorBubbling If true, all violations are treated as collection-level errors
     */
    public function mapViolationsAgainstGroups(
        QuickAddRowCollection $quickAddRowCollection,
        iterable $constraintViolations,
        array $groups,
        bool $errorBubbling = false
    ): void {
        $groupedViolations = $this->groupViolationsByConstraint($constraintViolations);

        foreach ($groupedViolations as $violationsGroup) {
            foreach ($violationsGroup as $violation) {
                $this->processViolation(
                    $quickAddRowCollection,
                    $violation,
                    $violationsGroup,
                    $groups,
                    $errorBubbling
                );
            }
        }
    }

    /**
     * @param iterable<ConstraintViolation> $violations
     * @return array<string, ConstraintViolation[]>
     */
    private function groupViolationsByConstraint(iterable $violations): array
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

    private function processViolation(
        QuickAddRowCollection $quickAddRowCollection,
        ConstraintViolationInterface $violation,
        array $violationsGroup,
        array $groups,
        bool $errorBubbling
    ): void {
        $messageData = $this->extractMessageData($violation);

        if ($errorBubbling) {
            $quickAddRowCollection->addError($messageData['message'], $messageData['parameters']);
            return;
        }

        [$index, $propertyName] = $this->extractFromPropertyPath($violation->getPropertyPath());

        if ($index === null) {
            $quickAddRowCollection->addError($messageData['message'], $messageData['parameters']);
            return;
        }

        /** @var QuickAddRow|null $quickAddRow */
        $quickAddRow = $quickAddRowCollection[$index] ?? null;

        if ($quickAddRow === null) {
            return;
        }

        $isError = $this->isError($violationsGroup, $groups);

        if ($isError) {
            $quickAddRow->addError(
                $messageData['message'],
                $messageData['parameters'],
                $propertyName
            );
        } else {
            $quickAddRow->addWarning(
                $messageData['message'],
                $messageData['parameters'],
                $propertyName
            );
        }
    }

    private function extractMessageData(ConstraintViolationInterface $violation): array
    {
        return [
            'message' => $violation->getMessageTemplate() ?: $violation->getMessage(),
            'parameters' => $violation->getParameters()
        ];
    }

    private function isError(array $violationsGroup, array $groups): bool
    {
        if (empty($groups)) {
            return true;
        }

        $violatedValidationGroups = $this->extractViolatedGroups($violationsGroup);

        if (count($groups) > 1) {
            $groups = array_diff($groups, $this->excludedGroupsFromMultiGroupValidation);
        }

        return array_all($groups, fn ($group) => in_array($group, $violatedValidationGroups, true));
    }

    private function extractViolatedGroups(array $violationsGroup): array
    {
        $violatedValidationGroups = [];

        /**
         * @var ConstraintViolation $violation
         */
        foreach ($violationsGroup as $violation) {
            foreach ($violation->getConstraint()?->groups ?? [] as $group) {
                $violatedValidationGroups[] = $group;
            }
        }

        return $violatedValidationGroups;
    }

    /**
     * @param string|null $path
     * @return array{?int, string}
     */
    private function extractFromPropertyPath(?string $path): array
    {
        if ((string) $path === '') {
            return [null, ''];
        }

        $propertyPath = new PropertyPath($path);
        $index = null;
        $propertyName = null;
        foreach ($propertyPath as $i => $element) {
            if ($propertyPath->isIndex($i)) {
                $index = (int)$element;
                continue;
            }
            if ($index !== null) {
                $propertyName = (string)$element;
                break;
            }
        }

        return [$index, (string) $propertyName];
    }

    private function getGroupConstraintKey(ConstraintViolation $violation): ?string
    {
        $constraint = $violation->getConstraint();

        if (!$constraint) {
            return null;
        }

        return $constraint->payload[self::CONSTRAINT_GROUP_KEY] ?? null;
    }
}
