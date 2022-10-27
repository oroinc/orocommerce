<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Removes products that are presented in search index, but were removed from DB already
 */
class FrontendProductCleanUpListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var AclHelper */
    private $aclHelper;

    public function __construct(DoctrineHelper $doctrineHelper, AclHelper $aclHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
    }

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

        $qb = $productRepository->getProductsQueryBuilder($requestedProductIds)->select('p.id');
        $existingProductIds = $this->aclHelper->apply($qb)->getArrayResult();

        if (count($requestedProductIds) != count($existingProductIds)) {
            $existingProductIds = array_column($existingProductIds, 'id');

            foreach ($records as $key => $record) {
                $productId = $record->getValue('id');
                if (!in_array($productId, $existingProductIds)) {
                    unset($records[$key]);
                }
            }

            // re-order array keys
            $event->setRecords(array_values($records));
        }
    }
}
