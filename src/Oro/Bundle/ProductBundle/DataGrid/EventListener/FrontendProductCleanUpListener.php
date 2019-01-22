<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Removes products that are presented in search index, but were removed from DB already
 */
class FrontendProductCleanUpListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param SearchResultAfter $event
     */
    public function onSearchResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecordInterface[] $records */
        $records = $event->getRecords();
        if (empty($records)) {
            return;
        }

        $requestedProductIds = [];
        foreach ($records as $record) {
            $requestedProductIds[] = $record->getValue('id');
        }

        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepository(Product::class);

        $existingProductIds = $productRepository->getProductsQueryBuilder($requestedProductIds)
            ->select('p.id')
            ->getQuery()
            ->getArrayResult();

        if (count($requestedProductIds) != count($existingProductIds)) {
            foreach ($existingProductIds as $key => $data) {
                $existingProductIds[$key] = $data['id'];
            }

            foreach ($records as $key => $record) {
                $productId = $record->getValue('id');
                if (!in_array($productId, $existingProductIds)) {
                    unset($records[$key]);
                }
            }

            $event->setRecords($records);
        }
    }
}
