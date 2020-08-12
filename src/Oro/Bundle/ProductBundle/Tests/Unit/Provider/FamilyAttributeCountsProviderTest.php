<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\ProductBundle\Provider\FamilyAttributeCountsProvider;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Component\DependencyInjection\ServiceLink;

class FamilyAttributeCountsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridManager;

    /** @var FamilyAttributeCountsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->datagridManager = $this->createMock(ManagerInterface::class);

        $datagridManagerLink = $this->createMock(ServiceLink::class);
        $datagridManagerLink
            ->expects($this->any())
            ->method('getService')
            ->willReturn($this->datagridManager);

        $this->provider = new FamilyAttributeCountsProvider($datagridManagerLink, $this->productRepository);
    }

    public function testGetFamilyAttributeCounts(): void
    {
        $datagridName = 'sample_datagrid';
        $gridDatasource = $this->createMock(SearchDatasource::class);
        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $gridDatasource->expects($this->any())
            ->method('getSearchQuery')
            ->willReturn($searchQuery);

        $datagrid = new Datagrid('datagrid', DatagridConfiguration::create([]), new ParameterBag([]));
        $datagrid->setDatasource($gridDatasource);
        $datagrid->setAcceptor(new Acceptor());

        $this->datagridManager
            ->expects($this->once())
            ->method('getDatagrid')
            ->with($datagridName)
            ->willReturn($datagrid);

        $this->productRepository->expects($this->atMost(1))
            ->method('getFamilyAttributeCountsQuery')
            ->with($searchQuery, 'familyAttributesCount')
            ->willReturnArgument(0);

        $result = $this->createMock(Result::class);
        $aggregatedData = ['sample_aggregated_data'];
        $result->expects($this->once())
            ->method('getAggregatedData')
            ->willReturn($aggregatedData);

        $searchQuery
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($result);

        $this->assertEquals($aggregatedData, $this->provider->getFamilyAttributeCounts($datagridName));
    }
}
