<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Provides family attribute counts for the datasource query of the specified datagrid.
 */
class FamilyAttributeCountsProvider
{
    /** @var ServiceLink */
    private $datagridManagerLink;

    /** @var ProductRepository */
    private $productRepository;

    /**
     * @param ServiceLink $datagridManagerLink
     * @param ProductRepository $productRepository
     */
    public function __construct(ServiceLink $datagridManagerLink, ProductRepository $productRepository)
    {
        $this->datagridManagerLink = $datagridManagerLink;
        $this->productRepository = $productRepository;
    }

    /**
     * @param string $datagridName
     *
     * @return array
     */
    public function getFamilyAttributeCounts(string $datagridName): array
    {
        /** @var Manager $datagridManager */
        $datagridManager = $this->datagridManagerLink->getService();

        /** @var SearchDatasource $datasource */
        $datasource = $datagridManager->getDatagrid($datagridName)
            ->acceptDatasource()
            ->getDatasource();

        return $this->productRepository
            ->getFamilyAttributeCountsQuery($datasource->getSearchQuery(), 'familyAttributesCount')
            ->getResult()
            ->getAggregatedData();
    }
}
