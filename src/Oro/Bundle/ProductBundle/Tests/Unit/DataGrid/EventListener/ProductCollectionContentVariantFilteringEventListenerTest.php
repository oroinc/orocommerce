<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\ProductCollectionContentVariantFilteringEventListener;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Handler\RequestContentVariantHandler;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class ProductCollectionContentVariantFilteringEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private const CONTENT_VARIANT_ID = 142;
    private const CONTENT_VARIANT_OTHER_TYPE_ID = 242;

    /** @var RequestContentVariantHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $requestHandler;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductCollectionContentVariantFilteringEventListener */
    private $listener;

    protected function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(ContentVariant::class)
            ->willReturn($entityManager);

        $this->requestHandler = $this->createMock(RequestContentVariantHandler::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new ProductCollectionContentVariantFilteringEventListener(
            $this->requestHandler,
            $doctrine,
            $this->configManager
        );

        $contentVariant = (new ContentVariantStub())
            ->setId(self::CONTENT_VARIANT_ID)
            ->setType(ProductCollectionContentVariantType::TYPE);
        $contentVariantOfOtherType = (new ContentVariantStub())
            ->setId(self::CONTENT_VARIANT_OTHER_TYPE_ID)
            ->setType('sample_type');
        $entityManager->expects($this->any())
            ->method('find')
            ->willReturnMap([
                [ContentVariant::class, self::CONTENT_VARIANT_ID, $contentVariant],
                [ContentVariant::class, self::CONTENT_VARIANT_OTHER_TYPE_ID, $contentVariantOfOtherType],
            ]);
    }

    /**
     * @dataProvider onPreBuildWhenContentVariantIdInParametersDataProvider
     */
    public function testOnPreBuildWhenContentVariantIdInParameters(
        array $parameters,
        array $expectedParameters,
        array $expectedConfig
    ): void {
        $this->requestHandler->expects($this->never())
            ->method('getContentVariantId');

        $this->requestHandler->expects($this->never())
            ->method('getOverrideVariantConfiguration');

        $event = new PreBuild(DatagridConfiguration::create([]), new ParameterBag($parameters));
        $this->listener->onPreBuild($event);

        $this->assertEquals($expectedParameters, $event->getParameters()->all());
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    public function onPreBuildWhenContentVariantIdInParametersDataProvider(): array
    {
        return [
            'content variant not exists' => [
                'parameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => 100,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedParameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => 100,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedConfig' => [],
            ],
            'content variant not of expected type' => [
                'parameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_OTHER_TYPE_ID,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedParameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_OTHER_TYPE_ID,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedConfig' => [],
            ],
            'content variant exists' => [
                'parameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_ID,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedParameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_ID,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedConfig' => [
                    'options' => [
                        'urlParams' => [
                            ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_ID,
                            ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider onPreBuildWhenContentVariantIdInRequestDataProvider
     */
    public function testOnPreBuildWhenContentVariantIdInRequest(
        array $parameters,
        int $contentVariantId,
        bool $overrideVariantConfiguration,
        array $expectedParameters,
        array $expectedConfig
    ): void {
        $this->requestHandler->expects($this->once())
            ->method('getContentVariantId')
            ->willReturn($contentVariantId);

        $this->requestHandler->expects($this->any())
            ->method('getOverrideVariantConfiguration')
            ->willReturn($overrideVariantConfiguration);

        $event = new PreBuild(DatagridConfiguration::create([]), new ParameterBag($parameters));
        $this->listener->onPreBuild($event);

        $this->assertEquals($expectedParameters, $event->getParameters()->all());
        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    public function onPreBuildWhenContentVariantIdInRequestDataProvider(): array
    {
        return [
            'content variant not set' => [
                'parameters' => [],
                'contentVariantId' => 0,
                'overrideVariantConfiguration' => true,
                'expectedParameters' => [],
                'expectedConfig' => [],
            ],
            'content variant not exists' => [
                'parameters' => [],
                'contentVariantId' => 100,
                'overrideVariantConfiguration' => true,
                'expectedParameters' => [],
                'expectedConfig' => [],
            ],
            'content variant not of expected type' => [
                'parameters' => [],
                'contentVariantId' => self::CONTENT_VARIANT_OTHER_TYPE_ID,
                'overrideVariantConfiguration' => true,
                'expectedParameters' => [],
                'expectedConfig' => [],
            ],
            'invalid content variant id in parameters' => [
                'parameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => -100,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'contentVariantId' => self::CONTENT_VARIANT_ID,
                'overrideVariantConfiguration' => true,
                'expectedParameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_ID,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedConfig' => [
                    'options' => [
                        'urlParams' => [
                            ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_ID,
                            ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => 1,
                        ],
                    ],
                ],
            ],
            'content variant exists' => [
                'parameters' => [],
                'contentVariantId' => self::CONTENT_VARIANT_ID,
                'overrideVariantConfiguration' => true,
                'expectedParameters' => [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_ID,
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true,
                ],
                'expectedConfig' => [
                    'options' => [
                        'urlParams' => [
                            ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => self::CONTENT_VARIANT_ID,
                            ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => 1,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testOnBuildAfterWhenDatasourceIsNotSearch()
    {
        $configuration = $this->createMock(DatagridConfiguration::class);
        $parameterBag = new ParameterBag([]);
        $grid = new Datagrid('name', $configuration, $parameterBag);
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
        $datasource = $this->createMock(SearchDatasource::class);
        $grid->setDatasource($datasource);
        $datasource->expects($this->never())
            ->method('getSearchQuery');

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
        $this->assertEmpty(
            $configuration->offsetGetByPath(
                ProductCollectionContentVariantFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH
            )
        );
    }

    public function testOnBuildAfter()
    {
        $contentVariantId = 777;
        $configuration = DatagridConfiguration::create([]);
        $configuration->offsetSetByPath(
            ProductCollectionContentVariantFilteringEventListener::CONTENT_VARIANT_ID_CONFIG_PATH,
            $contentVariantId
        );
        $configuration->offsetSetByPath(
            ProductCollectionContentVariantFilteringEventListener::OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH,
            true
        );
        $parameterBag = new ParameterBag([]);
        $grid = new Datagrid('name', $configuration, $parameterBag);
        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $searchQuery->expects($this->exactly(2))
            ->method('addWhere')
            ->withConsecutive(
                [Criteria::expr()->eq(sprintf('integer.assigned_to.variant_%s', $contentVariantId), 1)],
                [Criteria::expr()->gte('integer.is_variant', 0)]
            );
        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects($this->atLeastOnce())
            ->method('getSearchQuery')
            ->willReturn($searchQuery);
        $grid->setDatasource($datasource);

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
        $this->assertEquals(
            [
                SluggableUrlGenerator::CONTEXT_TYPE => 'content_variant',
                SluggableUrlGenerator::CONTEXT_DATA => $contentVariantId,
            ],
            $configuration->offsetGetByPath(
                ProductCollectionContentVariantFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH
            )
        );
    }

    public function testOnBuildAfterWhenVariationsHideCompletely()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.display_simple_variations')
            ->willReturn(Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY);

        $contentVariantId = 777;
        $configuration = DatagridConfiguration::create([]);
        $configuration->offsetSetByPath(
            ProductCollectionContentVariantFilteringEventListener::CONTENT_VARIANT_ID_CONFIG_PATH,
            $contentVariantId
        );
        $configuration->offsetSetByPath(
            ProductCollectionContentVariantFilteringEventListener::OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH,
            false
        );
        $parameterBag = new ParameterBag([]);
        $grid = new Datagrid('name', $configuration, $parameterBag);
        $searchQuery = $this->createMock(SearchQueryInterface::class);
        $searchQuery->expects($this->exactly(2))
            ->method('addWhere')
            ->withConsecutive(
                [Criteria::expr()->eq(sprintf('integer.assigned_to.variant_%s', $contentVariantId), 1)],
                [Criteria::expr()->orX(
                    Criteria::expr()->eq(sprintf('integer.manually_added_to.variant_%s', $contentVariantId), 1),
                    Criteria::expr()->eq('integer.is_variant', 0)
                )]
            );
        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects($this->atLeastOnce())
            ->method('getSearchQuery')
            ->willReturn($searchQuery);
        $grid->setDatasource($datasource);

        $event = new BuildAfter($grid);
        $this->listener->onBuildAfter($event);
        $this->assertEquals(
            [
                SluggableUrlGenerator::CONTEXT_TYPE => 'content_variant',
                SluggableUrlGenerator::CONTEXT_DATA => $contentVariantId,
            ],
            $configuration->offsetGetByPath(
                ProductCollectionContentVariantFilteringEventListener::VIEW_LINK_PARAMS_CONFIG_PATH
            )
        );
    }

    /**
     * @dataProvider onSearchResultBeforeDataProvider
     */
    public function testOnSearchResultBefore(array $parameters): void
    {
        $configurationMock = $this->createMock(DatagridConfiguration::class);
        $configurationMock->expects($this->once())
            ->method('offsetGetByPath')
            ->with(ProductCollectionContentVariantFilteringEventListener::CONTENT_VARIANT_ID_CONFIG_PATH)
            ->willReturn($parameters['contentVariantId']);

        $searchQuery = $this->createMock(SearchQueryInterface::class);
        if ($parameters['contentVariantId']) {
            $searchQuery->expects($this->once())
                ->method('getSortOrder')
                ->willReturn(empty($parameters['sortBy'])? null : 'ASC');

            if (empty($parameters['sortBy'])) {
                $searchQuery->expects($this->once())
                    ->method('setOrderBy')
                    ->with('decimal.assigned_to_sort_order.variant_' . $parameters['contentVariantId']);
            }
        }

        $grid = new Datagrid('name', $configurationMock, new ParameterBag([]));
        $event = new SearchResultBefore($grid, $searchQuery);

        $this->listener->onSearchResultBefore($event);
    }

    public function onSearchResultBeforeDataProvider(): array
    {
        return [
            'without variant' => [
                'parameters' => [
                    'contentVariantId' => null,
                    'sortBy' => []
                ]
            ],
            'with sort already defined' => [
                'parameters' => [
                    'contentVariantId' => 100,
                    'sortBy' => ['text.sku', 'ASC']
                ]
            ],
            'without sort predefined' => [
                'parameters' => [
                    'contentVariantId' => 100,
                    'sortBy' => []
                ]
            ]
        ];
    }
}
