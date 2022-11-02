<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private const KEY = 'search';

    /** @var SearchProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $searchProductHandler;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $searchRepository;

    /** @var SearchEventListener|\PHPUnit\Framework\MockObject\MockObject */
    private $listener;

    protected function setUp(): void
    {
        $this->searchProductHandler = $this->createMock(SearchProductHandler::class);
        $this->searchRepository = $this->createMock(ProductRepository::class);

        $this->listener = new SearchEventListener($this->searchProductHandler, $this->searchRepository);
    }

    /**
     * @param array $handler
     * @param array $parameterBug
     * @param mixed $expected
     *
     * @dataProvider preBuildProvider
     */
    public function testOnPreBuild($handler, $parameterBug, $expected): void
    {
        $this->searchProductHandler
            ->expects($handler['expects'])
            ->method('getSearchString')
            ->willReturn($handler['value']);

        $configuration = DatagridConfiguration::create([]);
        $parameterBag = new ParameterBag($parameterBug['value']);
        $event = new PreBuild($configuration, $parameterBag);

        $this->listener->onPreBuild($event);
        self::assertEquals($event->getConfig()->offsetGetByPath($this->getConfigPath()), $expected);
        self::assertEquals($parameterBag->get(SearchProductHandler::SEARCH_KEY), $expected);
    }

    public function preBuildProvider(): array
    {
        return [
            'with handler return string and parameters return null' => [
                'handler' => [
                    'expects' => self::once(),
                    'value' => self::KEY
                ],
                'parameter_bug' => [
                    'expects' => self::once(),
                    'value' => []
                ],
                'expected' => self::KEY
            ],
            'with handler return null and parameters return string' => [
                'handler' => [
                    'expects' => self::never(),
                    'value' => self::KEY
                ],
                'parameter_bug' => [
                    'expects' => self::once(),
                    'value' => [self::KEY => self::KEY]
                ],
                'expected' => self::KEY
            ],
            'with handler return null and parameters return null' => [
                'handler' => [
                    'expects' => self::once(),
                    'value' => null
                ],
                'parameter_bug' => [
                    'expects' => self::once(),
                    'value' => []
                ],
                'expected' => null
            ],
            'with handler return null and parameters return empty string' => [
                'handler' => [
                    'expects' => self::once(),
                    'value' => ''
                ],
                'parameter_bug' => [
                    'expects' => self::once(),
                    'value' => []
                ],
                'expected' => null
            ],
            'with handler return string and parameters return string' => [
                'handler' => [
                    'expects' => self::never(),
                    'value' => self::KEY
                ],
                'parameter_bug' => [
                    'expects' => self::once(),
                    'value' => [self::KEY => sprintf('%s-parameter_bug', self::KEY)]
                ],
                'expected' => sprintf('%s-parameter_bug', self::KEY)
            ]
        ];
    }

    /**
     * @dataProvider onBuildAfterProvider
     */
    public function testOnBuildAfter(array $searchText, array $searchCriteria, string $searchOperator): void
    {
        $this->searchRepository->expects($this->any())
            ->method('getProductSearchOperator')
            ->willReturn($searchOperator);

        /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $configuration */
        $configuration = $this->createMock(DatagridConfiguration::class);
        $configuration
            ->expects(self::once())
            ->method('offsetGetByPath')
            ->willReturn($searchText['value']);

        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $searchQuery
            ->expects($searchCriteria['expects'])
            ->method('addWhere')
            ->with(Criteria::expr()->$searchOperator('all_text_LOCALIZATION_ID', self::KEY));

        /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject $dataSource */
        $dataSource = $this->createMock(SearchDatasource::class);
        $dataSource
            ->method('getSearchQuery')
            ->willReturn($searchQuery);

        $grid = new Datagrid('grid_name', $configuration, new ParameterBag([]));
        $grid->setDatasource($dataSource);

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
    }

    public function onBuildAfterProvider(): array
    {
        return [
            'with search string null' => [
                'search_text' => [
                    'value' => null,
                ],
                'search_query' => [
                    'expects' => self::never(),
                ],
                'searchOperator' => 'contains',
            ],
            'with search string null and like operator' => [
                'search_text' => [
                    'value' => null,
                ],
                'search_query' => [
                    'expects' => self::never(),
                ],
                'searchOperator' => 'like',
            ],
            'with search string empty' => [
                'search_text' => [
                    'value' => '',
                ],
                'search_query' => [
                    'expects' => self::never(),
                ],
                'searchOperator' => 'contains',
            ],
            'with search string empty and like operator' => [
                'search_text' => [
                    'value' => '',
                ],
                'search_query' => [
                    'expects' => self::never(),
                ],
                'searchOperator' => 'like',
            ],
            'with search string' => [
                'search_text' => [
                    'value' => self::KEY,
                ],
                'search_criteria' => [
                    'expects' => self::once(),
                ],
                'searchOperator' => 'contains',
            ],
            'with search string and like operator' => [
                'search_text' => [
                    'value' => self::KEY,
                ],
                'search_criteria' => [
                    'expects' => self::once(),
                ],
                'searchOperator' => 'like',
            ]
        ];
    }

    private function getConfigPath(): string
    {
        return sprintf('[options][urlParams][%s]', self::KEY);
    }
}
