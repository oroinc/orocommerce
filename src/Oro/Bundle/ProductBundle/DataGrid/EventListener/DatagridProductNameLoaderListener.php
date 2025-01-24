<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Loads product names collections to reduce DB queries count in a datagrid.
 */
class DatagridProductNameLoaderListener
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private string $idFieldName,
        private string $namesFieldName
    ) {
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        $records = $event->getRecords();

        $productIds = [];
        foreach ($records as $record) {
            $productIds[] = $record->getValue($this->idFieldName);
        }

        $productNamesCollections = $this->doctrine->getRepository(Product::class)
            ->getProductNamesByProductIds($productIds);

        foreach ($records as $record) {
            $record->setValue(
                $this->namesFieldName,
                $productNamesCollections[$record->getValue($this->idFieldName)] ?? new ArrayCollection()
            );
        }
    }
}
