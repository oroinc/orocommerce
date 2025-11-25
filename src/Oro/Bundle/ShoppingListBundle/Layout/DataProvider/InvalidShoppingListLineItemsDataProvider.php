<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;

/**
 * Provides invalid line items ids for a shopping list.
 */
class InvalidShoppingListLineItemsDataProvider implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    public function __construct(
        private readonly InvalidShoppingListLineItemsProvider $provider
    ) {
    }

    /**
     * @return int[] Sorted array of line item IDs: first with errors, then with warnings (no duplicates)
     */
    public function getInvalidLineItemsIds(ShoppingList $shoppingList, ?string $validationGroupType = null): array
    {
        if (!$this->isFeaturesEnabled()) {
            return [];
        }

        return $this->provider->getInvalidLineItemsIds($shoppingList->getLineItems(), $validationGroupType);
    }

    /**
     * @return array{
     *     errors: list<int>,
     *     warnings: list<int>
     * } Array with line item IDs grouped by severity (no duplicates)
     */
    public function getInvalidLineItemsIdsBySeverity(
        ShoppingList $shoppingList,
        ?string $validationGroupType = null
    ): array {
        if (!$this->isFeaturesEnabled()) {
            return [
                'errors' => [],
                'warnings' => [],
            ];
        }

        return $this->provider->getInvalidLineItemsIdsBySeverity($shoppingList->getLineItems(), $validationGroupType);
    }
}
