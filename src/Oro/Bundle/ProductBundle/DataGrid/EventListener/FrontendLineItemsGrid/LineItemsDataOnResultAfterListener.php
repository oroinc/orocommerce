<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid;

use Doctrine\ORM\EntityManager;
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
    public const LINE_ITEMS = 'lineItemsByIds';
    public const LINE_ITEMS_DATA = 'lineItemsDataByIds';

    private EventDispatcherInterface $eventDispatcher;

    private EntityClassResolver $entityClassResolver;

    public function __construct(EventDispatcherInterface $eventDispatcher, EntityClassResolver $entityClassResolver)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->entityClassResolver = $entityClassResolver;
    }

    public function onResultAfter(OrmResultAfter $event): void
    {
        [$lineItemsByIds, $lineItemsRecordsByIds] = $this->getLineItemsIndexedById($event);
        if (!$lineItemsByIds) {
            return;
        }

        $lineItemsData = $this->getLineItemsData($lineItemsByIds, $lineItemsRecordsByIds, $event->getDatagrid());

        foreach ($event->getRecords() as $record) {
            $lineItemsIds = array_flip($this->getLineItemIds($record));
            /** @var ProductLineItemInterface[] $recordLineItems */
            $recordLineItems = array_intersect_key($lineItemsByIds, $lineItemsIds);
            $recordLineItemsData = array_intersect_key($lineItemsData, $lineItemsIds);

            $record->setValue(self::LINE_ITEMS, $recordLineItems);
            $record->setValue(self::LINE_ITEMS_DATA, $recordLineItemsData);
        }
    }

    /**
     * @param ResultRecordInterface $record
     * @return int[]|string[]
     */
    private function getLineItemIds(ResultRecordInterface $record): array
    {
        return array_filter(explode(',', (string)($record->getValue('allLineItemsIds') ?: $record->getValue('id'))));
    }

    private function isGrouped(DatagridInterface $datagrid): bool
    {
        $parameters = $datagrid->getParameters()->get('_parameters', []);

        return isset($parameters['group']) ? filter_var($parameters['group'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    /**
     * @param OrmResultAfter $event
     *
     * @return array{array<int,ProductLineItemInterface>, array<int,array>}
     */
    private function getLineItemsIndexedById(OrmResultAfter $event): array
    {
        $records = $event->getRecords();
        if (!$records) {
            return [[], []];
        }

        $rootEntityClass = $this->getRootEntityClass($event->getDatagrid());
        $lineItemsByIds = [];
        $lineItemsDataByIds = [];

        /** @var EntityManager $entityManager */
        $entityManager = $event->getQuery()->getEntityManager();
        foreach ($records as $record) {
            $lineItemIds = $this->getLineItemIds($record);
            foreach ($lineItemIds as $lineItemId) {
                $lineItemsByIds[$lineItemId] = $entityManager->getReference($rootEntityClass, (int)$lineItemId);
                $lineItemsDataByIds[$lineItemId] = $record->getDataArray();
            }
        }

        return [$lineItemsByIds, $lineItemsDataByIds];
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

    /**
     * @param array<int,ProductLineItemInterface> $lineItemsByIds
     * @param array<int,array> $lineItemsDataByIds
     *
     * @param DatagridInterface $datagrid
     * @return array<int,array>
     */
    private function getLineItemsData(
        array $lineItemsByIds,
        array $lineItemsDataByIds,
        DatagridInterface $datagrid
    ): array {
        $lineItemsDataEvent = new DatagridLineItemsDataEvent(
            $lineItemsByIds,
            $lineItemsDataByIds,
            $datagrid,
            ['isGrouped' => $this->isGrouped($datagrid)]
        );

        $this->eventDispatcher->dispatch($lineItemsDataEvent, $lineItemsDataEvent->getName());

        return $lineItemsDataEvent->getDataForAllLineItems();
    }
}
