<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;

/**
 * Extension is responsible for filtering/extra data for invalid line items datagrid
 */
class InvalidShoppingListLineItemsExtension extends AbstractExtension
{
    private const string GRID_NAME = 'frontend-customer-user-shopping-list-invalid-line-items-grid';

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly InvalidShoppingListLineItemsProvider $provider
    ) {
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return $config->getName() === self::GRID_NAME;
    }

    #[\Override]
    public function getPriority(): int
    {
        return -200;
    }

    #[\Override]
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource): void
    {
        if (!$datasource instanceof OrmDatasource) {
            return;
        }

        $invalidItemIds = $this->getInvalidLineItemsIds();

        if (is_null($invalidItemIds)) {
            return;
        }

        if (empty($invalidItemIds)) {
            // If no invalid items, show nothing
            $datasource->getQueryBuilder()->andWhere('1 = 0');
            return;
        }

        $qb = $datasource->getQueryBuilder();
        $qb->setParameter('invalid_items_ids', $invalidItemIds);
    }

    #[\Override]
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data): void
    {
        $invalidLineItemIds = $this->getInvalidLineItemsIds();

        if (is_null($invalidLineItemIds)) {
            return;
        }

        $data->offsetSetByPath('[invalidIds]', $invalidLineItemIds);
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result): void
    {
        $invalidLineItemIds = $this->getInvalidLineItemsIds();

        if (is_null($invalidLineItemIds)) {
            return;
        }

        $result->offsetAddToArrayByPath(
            '[metadata]',
            [
                'invalidIds' => $invalidLineItemIds
            ]
        );
    }

    private function getInvalidLineItemsIds(): ?array
    {
        $shoppingListId = $this->parameters->get('shopping_list_id');
        if (!$shoppingListId) {
            return null;
        }

        $triggeredBy = $this->parameters->get('triggered_by');

        if (!$triggeredBy) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Parameter "triggered_by" is required for the the "%s" datagrid',
                    self::GRID_NAME
                )
            );
        }

        $shoppingList = $this->doctrine->getRepository(ShoppingList::class)->find($shoppingListId);
        if (!$shoppingList) {
            return null;
        }

        return $this->provider->getInvalidLineItemsIds($shoppingList->getLineItems(), $triggeredBy);
    }
}
