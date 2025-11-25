<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Mapper\ShoppingListInvalidLineItemsMapper;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides functionality to identify invalid line items in a shopping list and sort them by severity.
 */
class InvalidShoppingListLineItemsProvider implements ResetInterface
{
    public const string ERRORS = 'errors';
    public const string WARNINGS = 'warnings';

    private const string HASH_ALGORITHM = 'sha256';

    private array $cache = [];

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly ProductLineItemsHolderFactoryInterface $lineItemsHolderFactory,
        private readonly ShoppingListInvalidLineItemsMapper $shoppingListInvalidLineItemsMapper,
        private readonly ShoppingListValidationGroupsProvider $validationGroupsProvider
    ) {
    }

    #[\Override]
    public function reset(): void
    {
        $this->cache = [];
    }

    /**
     * @return int[] Sorted array of line item IDs: first with errors, then with warnings (no duplicates)
     */
    public function getInvalidLineItemsIds(Collection|array $lineItems, ?string $validationGroupType = null): array
    {
        $invalidLineItems = $this->getInvalidItemsViolations($lineItems, $validationGroupType);

        return \array_unique([
            ...\array_keys($invalidLineItems[self::ERRORS] ?? []),
            ...\array_keys($invalidLineItems[self::WARNINGS] ?? [])
        ]);
    }

    /**
     * @return array{
     *     errors: list<int>,
     *     warnings: list<int>
     * } Array with line item IDs grouped by severity (no duplicates)
     */
    public function getInvalidLineItemsIdsBySeverity(
        Collection|array $lineItems,
        ?string $validationGroupType = null
    ): array {
        $invalidLineItems = $this->getInvalidItemsViolations($lineItems, $validationGroupType);

        $errors = \array_keys($invalidLineItems[self::ERRORS] ?? []);
        $warnings = \array_keys($invalidLineItems[self::WARNINGS] ?? []);

        return [
            self::ERRORS => $errors,
            self::WARNINGS => $warnings,
        ];
    }

    /**
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
    public function getInvalidItemsViolations(Collection|array $lineItems, ?string $validationGroupType = null): array
    {
        if (!$lineItems || ($lineItems instanceof Collection && $lineItems->isEmpty())) {
            return [
                self::ERRORS => [],
                self::WARNINGS => [],
            ];
        }

        $validationGroups = $this->getValidationGroups($validationGroupType);

        if (!$validationGroups) {
            return [
                self::ERRORS => [],
                self::WARNINGS => [],
            ];
        }

        $cacheKey = $this->getCacheKey($lineItems, $validationGroups);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $violationList = $this->validator->validate(
            $this->lineItemsHolderFactory->createFromLineItems($lineItems),
            null,
            ValidationGroupUtils::resolveValidationGroups($validationGroups)
        );

        $result = $this->shoppingListInvalidLineItemsMapper->mapViolationListBySeverity(
            $violationList,
            $validationGroups
        );

        $this->cache[$cacheKey] = $result;

        return $result;
    }

    private function getCacheKey(Collection|array $lineItems, array $validationGroups): string
    {
        $shoppingList = $this->getShoppingList($lineItems);
        $ids = [$shoppingList?->getId()];

        foreach ($lineItems as $lineItem) {
            $ids[] = $lineItem->getId();

            if ($lineItem instanceof ProductKitItemLineItemsAwareInterface) {
                foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                    $ids[] = $kitItemLineItem->getId();
                }
            }
        }

        $data = [...$ids, ...$validationGroups];
        \sort($data);

        return \hash(self::HASH_ALGORITHM, \implode('', $data));
    }

    private function getShoppingList(Collection|array $lineItems): ?ShoppingList
    {
        $lineItem = match (true) {
            $lineItems instanceof Collection => $lineItems->first(),
            default => \reset($lineItems)
        };

        return match (true) {
            $lineItem instanceof LineItem => $lineItem->getAssociatedList(),
            $lineItem instanceof ProductKitItemLineItem => $lineItem->getLineItem()?->getAssociatedList(),
            default => null
        };
    }

    /**
     * @param Collection|array $lineItems
     * @return list<string>
     * @throws \InvalidArgumentException
     */
    private function getValidationGroups(?string $validationGroupType = null): array
    {
        if (!$validationGroupType) {
            return $this->validationGroupsProvider->getAllValidationGroups();
        }

        return [$this->validationGroupsProvider->getValidationGroupByType($validationGroupType)];
    }
}
