<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchContentVariantFilteringEventListener;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchContentVariantFilteringEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestContentVariantHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestHandler;

    /**
     * @var SearchContentVariantFilteringEventListener
     */
    private $listener;

    protected function setUp()
    {
        $this->requestHandler = $this->createMock(RequestContentVariantHandler::class);
        $this->listener = new SearchContentVariantFilteringEventListener($this->requestHandler);
    }

    public function testOnPreBuildWhenContentVariantIdFromEvent()
    {
        $contentVariantId = 777;
        $configuration = DatagridConfiguration::create([]);
        $parameterBag = new ParameterBag([
            ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => $contentVariantId,
        ]);
        $this->requestHandler->expects($this->never())
            ->method('getContentVariantId');

        $event = new PreBuild($configuration, $parameterBag);
        $this->listener->onPreBuild($event);
        $this->assertEquals(
            $contentVariantId,
            $configuration->offsetGetByPath(SearchContentVariantFilteringEventListener::CONTENT_VARIANT_ID_CONFIG_PATH)
        );
    }

    public function testOnPreBuildWhenContentVariantIdFromRequestHandler()
    {
        $contentVariantId = 777;
        $configuration = DatagridConfiguration::create([]);
        $parameterBag = new ParameterBag([]);
        $this->requestHandler->expects($this->once())
            ->method('getContentVariantId')
            ->willReturn($contentVariantId);

        $event = new PreBuild($configuration, $parameterBag);
        $this->listener->onPreBuild($event);
        $this->assertEquals(
            $contentVariantId,
            $configuration->offsetGetByPath(
                SearchContentVariantFilteringEventListener::CONTENT_VARIANT_ID_CONFIG_PATH
            )
        );
    }

    public function testOnPreBuildWhenNoContentVariantId()
    {
        $configuration = DatagridConfiguration::create([]);
        $parameterBag = new ParameterBag([]);
        $this->requestHandler->expects($this->once())
            ->method('getContentVariantId')
            ->willReturn(null);

        $event = new PreBuild($configuration, $parameterBag);
        $this->listener->onPreBuild($event);
        $this->assertEmpty(
            $configuration->offsetGetByPath(
                SearchContentVariantFilteringEventListener::CONTENT_VARIANT_ID_CONFIG_PATH
            )
        );
    }

    public function testOnBuildAfterWhenDatasourceIsNotSearch()
    {
        /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $configuration */
        $configuration = $this->createMock(DatagridConfiguration::class);
        $parameterBag = new ParameterBag([]);
        $grid = new Datagrid('name', $configuration, $parameterBag);
        /** @var DatasourceInterface $datasource */
        $datasource = $this->createMock(DatasourceInterface::class);
        $grid->setDatasource($datasource);
        $configuration->expects($this->never())
            ->method('offsetGetByPath');

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterWhenNoContentVariantId()
    {
        $configuration = DatagridConfiguration::create([]);
        $parameterBag = new ParameterBag([]);
        $grid = new Datagrid('name', $configuration, $parameterBag);
        /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject $datasource */
        $datasource = $this->createMock(SearchDatasource::class);
        $grid->setDatasource($datasource);
        $datasource->expects($this->never())
            ->method('getSearchQuery');

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
        $this->assertEmpty(
            $configuration->offsetGetByPath(SearchContentVariantFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH)
        );
    }

    public function testOnBuildAfter()
    {
        $contentVariantId = 777;
        $configuration = DatagridConfiguration::create([]);
        $configuration->offsetSetByPath(
            SearchContentVariantFilteringEventListener::CONTENT_VARIANT_ID_CONFIG_PATH,
            $contentVariantId
        );
        $parameterBag = new ParameterBag([]);
        $grid = new Datagrid('name', $configuration, $parameterBag);
        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $searchQuery->expects($this->once())
            ->method('addWhere')
            ->with(Criteria::expr()->eq(sprintf('integer.assigned_to_variant_%s', $contentVariantId), 1));
        /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject $datasource */
        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($searchQuery);
        $grid->setDatasource($datasource);

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
        $this->assertEquals(
            [
                SluggableUrlGenerator::CONTEXT_TYPE => 'content_variant',
                SluggableUrlGenerator::CONTEXT_DATA => $contentVariantId
            ],
            $configuration->offsetGetByPath(SearchContentVariantFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH)
        );
    }
}
