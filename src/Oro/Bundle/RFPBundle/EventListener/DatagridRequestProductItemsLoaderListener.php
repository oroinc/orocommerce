<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;

/**
 * Sets `requestProductItems` to each record of the datagrid showing {@see RequestProduct} records.
 */
class DatagridRequestProductItemsLoaderListener
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        $records = $event->getRecords();

        $requestProductIds = [];
        foreach ($records as $record) {
            $requestProductIds[] = $record->getValue('id');
        }

        $productItemsCollections = $this->doctrine->getRepository(RequestProductItem::class)
            ->getProductItemsByRequestIds($requestProductIds);

        foreach ($records as $record) {
            $record->setValue(
                'requestProductItems',
                $productItemsCollections[$record->getValue('id')] ?? new ArrayCollection()
            );
        }
    }
}
