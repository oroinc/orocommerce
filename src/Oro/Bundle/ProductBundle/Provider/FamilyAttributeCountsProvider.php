<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;

/**
 * Provides family attribute counts for the datasource query of the specified datagrid.
 */
class FamilyAttributeCountsProvider
{
    /** @var ManagerInterface */
    private $datagridManager;

    /** @var ProductRepository */
    private $productRepository;

    public function __construct(ManagerInterface $datagridManager, ProductRepository $productRepository)
    {
        $this->datagridManager = $datagridManager;
        $this->productRepository = $productRepository;
    }

    public function getFamilyAttributeCounts(string $datagridName): array
    {
        /** @var SearchDatasource $datasource */
        $datasource = $this->datagridManager->getDatagrid($datagridName)
            ->acceptDatasource()
            ->getDatasource();

        return $this->productRepository
            ->getFamilyAttributeCountsQuery($datasource->getSearchQuery(), 'familyAttributesCount')
            ->getResult()
            ->getAggregatedData();
    }
}
