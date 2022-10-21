<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\EventListener\FrontendCategorySortOrderDatagridListener;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class FrontendCategorySortOrderDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->listener = new FrontendCategorySortOrderDatagridListener();
    }

    /**
     * @dataProvider onSearchResultBeforeDataProvider
     */
    public function testOnSearchResultBefore(array $parameters): void
    {
        $gridParameters = new ParameterBag([]);
        /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $configuration */
        $configurationMock = $this->createMock(DatagridConfiguration::class);

        /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject $searchQuery */
        $searchQuery = $this->createMock(SearchQueryInterface::class);
        if ($parameters['categoryId']) {
            $gridParameters = new ParameterBag([RequestProductHandler::CATEGORY_ID_KEY => $parameters['categoryId']]);
            $searchQuery->expects($this->once())
                ->method('getSortOrder')
                ->willReturn(empty($parameters['sortBy'])? null : 'ASC');

            if (empty($parameters['sortBy'])) {
                $searchQuery->expects($this->once())
                    ->method('setOrderBy')
                    ->with('decimal.category_sort_order');
            }
        }

        $grid = new Datagrid('name', $configurationMock, $gridParameters);
        $event = new SearchResultBefore($grid, $searchQuery);

        $this->listener->onSearchResultBefore($event);
    }

    public function onSearchResultBeforeDataProvider(): array
    {
        return [
            'without variant' => [
                'parameters' => [
                    'categoryId' => null,
                    'sortBy' => []
                ]
            ],
            'with sort already defined' => [
                'parameters' => [
                    'categoryId' => 100,
                    'sortBy' => ['text.sku', 'ASC']
                ]
            ],
            'without sort predefined' => [
                'parameters' => [
                    'categoryId' => 100,
                    'sortBy' => []
                ]
            ]
        ];
    }
}
