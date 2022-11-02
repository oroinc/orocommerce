<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Populates line items records with lineItemsByIds and lineItemsDataByIds.
 */
class LineItemsDataOnResultAfterListener
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityClassResolver */
    private $entityClassResolver;

    public function __construct(EventDispatcherInterface $eventDispatcher, EntityClassResolver $entityClassResolver)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityClassResolver = $entityClassResolver;
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        $lineItemsByIds = $this->getLineItemsIndexedByIds($event);
        if (!$lineItemsByIds) {
            return;
        }

        $lineItemsData = $this->getLineItemsData($lineItemsByIds, $event->getDatagrid());

        foreach ($event->getRecords() as $record) {
            $lineItemsIds = array_flip(explode(',', $this->getRowId($record)));
            /** @var ProductLineItemInterface[] $recordLineItems */
            $recordLineItems = array_intersect_key($lineItemsByIds, $lineItemsIds);
            $recordLineItemsData = array_intersect_key($lineItemsData, $lineItemsIds);

            $record->setValue('lineItemsByIds', $recordLineItems);
            $record->setValue('lineItemsDataByIds', $recordLineItemsData);
        }
    }

    private function getRowId(ResultRecordInterface $record): string
    {
        return (string)($record->getValue('allLineItemsIds') ?: $record->getValue('id'));
    }

    private function isGrouped(DatagridInterface $datagrid): bool
    {
        $parameters = $datagrid->getParameters()->get('_parameters', []);

        return isset($parameters['group']) ? filter_var($parameters['group'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    /**
     * @param OrmResultAfter $event
     *
     * @return ProductLineItemInterface[]
     */
    private function getLineItemsIndexedByIds(OrmResultAfter $event): array
    {
        $records = $event->getRecords();
        if (!$records) {
            return [];
        }

        $lineItemsIds = array_filter(
            array_merge(
                ...array_map(fn (ResultRecordInterface $record) => explode(',', $this->getRowId($record)), $records)
            )
        );

        if (!$lineItemsIds) {
            return [];
        }

        $rootEntityClass = $this->getRootEntityClass($event->getDatagrid());
        $lineItemsByIds = [];
        $entityManager = $event->getQuery()->getEntityManager();
        foreach ($lineItemsIds as $lineItemId) {
            $lineItemsByIds[$lineItemId] = $entityManager->getReference($rootEntityClass, (int)$lineItemId);
        }

        return $lineItemsByIds;
    }

    private function getRootEntityClass(DatagridInterface $datagrid): string
    {
        $rootEntityClass = $datagrid->getConfig()->getOrmQuery()->getRootEntity($this->entityClassResolver);
        if (!is_a($rootEntityClass, ProductLineItemInterface::class, true)) {
            throw new \LogicException(
                sprintf(
                    'An entity with interface %s was expected, got %s',
                    ProductLineItemInterface::class,
                    $rootEntityClass
                )
            );
        }

        return $rootEntityClass;
    }

    private function getLineItemsData(
        array $lineItems,
        DatagridInterface $datagrid
    ): array {
        $lineItemsDataEvent = new DatagridLineItemsDataEvent(
            $lineItems,
            $datagrid,
            ['isGrouped' => $this->isGrouped($datagrid)]
        );
        $this->eventDispatcher->dispatch($lineItemsDataEvent, $lineItemsDataEvent->getName());

        return $lineItemsDataEvent->getDataForAllLineItems();
    }
}
