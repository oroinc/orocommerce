<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\SearchEventListener;
use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private const KEY = 'search';

    /**  @var SearchProductHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $searchProductHandler;

    /** @var SearchEventListener|\PHPUnit\Framework\MockObject\MockObject */
    private $listener;

    protected function setUp(): void
    {
        $this->searchProductHandler = self::createMock(SearchProductHandler::class);
        $this->listener = new SearchEventListener($this->searchProductHandler);
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
    }

    /**
     * @return array
     */
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
     * @param array $searchText
     * @param array $searchCriteria
     *
     * @dataProvider onBuildAfterProvider
     */
    public function testOnBuildAfter($searchText, $searchCriteria): void
    {
        /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject $configuration */
        $configuration = self::createMock(DatagridConfiguration::class);
        $configuration
            ->expects(self::once())
            ->method('offsetGetByPath')
            ->willReturn($searchText['value']);

        $searchQuery = self::createMock(SearchQueryInterface::class);
        $searchQuery
            ->expects($searchCriteria['expects'])
            ->method('addWhere')
            ->with(Criteria::expr()->contains('all_text_LOCALIZATION_ID', self::KEY));

        /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject $dataSource */
        $dataSource = self::createMock(SearchDatasource::class);
        $dataSource
            ->method('getSearchQuery')
            ->willReturn($searchQuery);

        $grid = new Datagrid('grid_name', $configuration, new ParameterBag([]));
        $grid->setDatasource($dataSource);

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
    }

    /**
     * @return array
     */
    public function onBuildAfterProvider(): array
    {
        return [
            'with search string null' => [
                'search_text' => [
                    'value' => null,
                ],
                'search_query' => [
                    'expects' => self::never(),
                ]
            ],
            'with search string empty' => [
                'search_text' => [
                    'value' => '',
                ],
                'search_query' => [
                    'expects' => self::never(),
                ]
            ],
            'with search string' => [
                'search_text' => [
                    'value' => self::KEY,
                ],
                'search_criteria' => [
                    'expects' => self::once(),
                ]
            ]
        ];
    }

    /**
     * @return string
     */
    private function getConfigPath(): string
    {
        return sprintf('[options][urlParams][%s]', self::KEY);
    }
}
