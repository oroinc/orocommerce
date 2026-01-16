<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;

/**
 * Listener is responsible for prioritizing invalid line items within a shopping list by moving them to the top
 */
class InvalidShoppingListLineItemsSortGridListener
{
    private const string INVALID_ITEMS_IDS = 'invalid_items_ids';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly InvalidShoppingListLineItemsProvider $provider
    ) {
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        $parameters = $event->getDatagrid()->getParameters();
        if (!$this->isDefaultSort($parameters)) {
            return;
        }

        $shoppingListId = $parameters->get('shopping_list_id');
        if (!$shoppingListId) {
            return;
        }

        $shoppingList = $this->doctrine->getRepository(ShoppingList::class)->find($shoppingListId);
        if (!$shoppingList) {
            return;
        }

        $parameters->set(
            self::INVALID_ITEMS_IDS,
            $this->provider->getInvalidLineItemsIdsBySeverity($shoppingList->getLineItems())
        );
    }

    public function onResultBeforeQuery(OrmResultBeforeQuery $event): void
    {
        $parameters = $event->getDatagrid()->getParameters();
        if (!$this->isDefaultSort($parameters)) {
            return;
        }

        $invalidItemIds = $parameters->get(self::INVALID_ITEMS_IDS, []);
        if (
            empty($invalidItemIds[InvalidShoppingListLineItemsProvider::ERRORS]) &&
            empty($invalidItemIds[InvalidShoppingListLineItemsProvider::WARNINGS])
        ) {
            return;
        }

        $this->addSortByInvalidIds($event->getQueryBuilder(), $invalidItemIds);
    }

    /**
     * @param $itemIds array<int, array<string, int[]>>
     * Example structure: ['error' => [1, 2, 3], 'warning' => [4, 5]]
     */
    private function addSortByInvalidIds(QueryBuilder $qb, array $itemIds): void
    {
        $qb->setParameter('invalid_error_line_items', $itemIds[InvalidShoppingListLineItemsProvider::ERRORS] ?? [])
            ->setParameter('invalid_warning_line_items', $itemIds[InvalidShoppingListLineItemsProvider::WARNINGS] ?? [])
            ->orderBy('CASE WHEN MIN(lineItem.id) in (:invalid_error_line_items) THEN 1 ' .
                'WHEN MIN(lineItem.id) in (:invalid_warning_line_items) THEN 2 ELSE 3 END');
    }

    private function isDefaultSort(ParameterBag $parameters): bool
    {
        $sortBy = $parameters->get(AbstractSorterExtension::SORTERS_ROOT_PARAM);

        return empty($sortBy) || isset($sortBy['id']);
    }
}
