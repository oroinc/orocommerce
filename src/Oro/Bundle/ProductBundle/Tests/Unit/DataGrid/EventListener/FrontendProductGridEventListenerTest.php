<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Type;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendProductGridEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type as SearchableType;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const LABEL = 'oro.test.label';
    private const LIMIT_FILTERS_SORTERS = 'oro_product.limit_filters_sorters_on_product_listing';

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeTypeRegistry;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeConfigProvider;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var AttributeFamilyRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeFamilyRepository;

    /** @var DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridConfig;

    /** @var SearchQueryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchQuery;

    /** @var DatagridInterface */
    private $datagrid;

    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtersStateProvider;

    /** @var DatagridStateProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $sortersStateProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FrontendProductGridEventListener */
    private $listener;

    protected function setUp()
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn(
                $this->getConfig(['label' => self::LABEL])
            );

        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);

        /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(EntityConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['entity', $entityConfigProvider],
                    ['extend', $this->extendConfigProvider],
                    ['attribute', $this->attributeConfigProvider],
                ]
            );

        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->metadata->expects($this->any())
            ->method('getAssociationMapping')
            ->willReturn(['targetEntity' => StubEnumValue::class]);

        $this->attributeFamilyRepository = $this->createMock(AttributeFamilyRepository::class);

        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn($this->metadata);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($this->attributeFamilyRepository);

        $this->datagridConfig = DatagridConfiguration::create(['name' => 'test_name']);
        $this->searchQuery = $this->createMock(SearchQueryInterface::class);

        /** @var SearchDatasource|\PHPUnit\Framework\MockObject\MockObject $gridDatasource */
        $gridDatasource = $this->createMock(SearchDatasource::class);
        $gridDatasource->expects($this->any())
            ->method('getSearchQuery')
            ->willReturn($this->searchQuery);

        $this->datagrid = new Datagrid('datagrid', $this->datagridConfig, new ParameterBag([]));
        $this->datagrid->setDatasource($gridDatasource);
        $this->datagrid->setAcceptor(new Acceptor());

        $datagridManager = $this->createMock(ManagerInterface::class);
        $datagridManager->expects($this->any())
            ->method('getDatagrid')
            ->willReturn($this->datagrid);

        /** @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject $datagridManagerLink */
        $datagridManagerLink = $this->createMock(ServiceLink::class);
        $datagridManagerLink->expects($this->any())
            ->method('getService')
            ->willReturn($datagridManager);

        $this->filtersStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->sortersStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new FrontendProductGridEventListener(
            $this->attributeManager,
            $this->attributeTypeRegistry,
            new AttributeConfigurationProvider($configManager),
            $this->productRepository,
            $doctrineHelper,
            $datagridManagerLink,
            $this->filtersStateProvider,
            $this->sortersStateProvider,
            $this->configManager
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @dataProvider onPreBuildDataProvider
     *
     * @param FieldConfigModel $attribute
     * @param $attributeType
     * @param ConfigInterface $extendConfig
     * @param ConfigInterface $attributeConfig
     * @param bool $hasAssociation
     * @param bool $limitFiltersSorters
     * @param array $aggregatedData
     * @param array $filtersState
     * @param array $sortersState
     * @param array $expected
     */
    public function testOnPreBuild(
        FieldConfigModel $attribute,
        $attributeType,
        ConfigInterface $extendConfig,
        ConfigInterface $attributeConfig,
        $hasAssociation = true,
        $limitFiltersSorters = false,
        array $aggregatedData = [],
        array $filtersState = [],
        array $sortersState = [],
        array $expected = []
    ) {
        $this->attributeManager->expects($this->any())
            ->method('getAttributesByClass')
            ->with(Product::class)
            ->willReturn(new ArrayCollection([$attribute]));

        $this->attributeTypeRegistry->expects($this->any())
            ->method('getAttributeType')
            ->with($attribute)
            ->willReturn($attributeType);

        $this->extendConfigProvider->expects($this->any())->method('getConfig')->willReturn($extendConfig);
        $this->attributeConfigProvider->expects($this->any())->method('getConfig')->willReturn($attributeConfig);

        $this->metadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturn($hasAssociation);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(self::LIMIT_FILTERS_SORTERS)
            ->willReturn($limitFiltersSorters);

        $this->productRepository->expects($this->any())
            ->method('getFamilyAttributeCountsQuery')
            ->with($this->searchQuery, 'familyAttributesCount')
            ->willReturnArgument(0);

        $this->searchQuery->expects($this->any())
            ->method('getResult')
            ->willReturn(new Result(new Query(), [], 0, ['familyAttributesCount' => $aggregatedData]));

        $this->attributeFamilyRepository->expects($this->any())
            ->method('getFamilyIdsForAttributes')
            ->with([$attribute->getId()])
            ->willReturn(
                [
                    $attribute->getId() => [$attribute->getId() + 1000, $attribute->getId() + 2000],
                ]
            );

        $this->filtersStateProvider->expects($this->any())
            ->method('getState')
            ->with($this->datagridConfig, $this->datagrid->getParameters())
            ->willReturn($filtersState);

        $this->sortersStateProvider->expects($this->any())
            ->method('getState')
            ->with($this->datagridConfig, $this->datagrid->getParameters())
            ->willReturn($sortersState);

        $event = new PreBuild($this->datagridConfig, $this->datagrid->getParameters());

        $this->listener->onPreBuild($event);

        $this->assertEquals(array_merge(['name' => 'test_name'], $expected), $this->datagridConfig->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function onPreBuildDataProvider()
    {
        $stringAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 101, 'fieldName' => 'sku']);
        $stringAttribute->setEntity(new EntityConfigModel());
        $stringSearchAttributeType = new SearchableType\StringSearchableAttributeType(new Type\StringAttributeType());

        $enumAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 202, 'fieldName' => 'internalStatus']);
        $enumAttribute->setEntity(new EntityConfigModel())
            ->fromArray('extend', ['target_entity' => StubEnumValue::class]);
        $enumSearchAttributeType = new SearchableType\EnumSearchableAttributeType(new Type\EnumAttributeType());

        $decimalAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 303, 'fieldName' => 'weight']);
        $decimalAttribute->setEntity(new EntityConfigModel());
        $decimalSearchAttributeType = new SearchableType\DecimalSearchableAttributeType(
            new Type\DecimalAttributeType('decimal')
        );

        $multiEnumAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 404, 'fieldName' => 'internalStatus']);
        $multiEnumAttribute->setEntity(new EntityConfigModel());
        $multiEnumSearchAttributeType = new SearchableType\MultiEnumSearchableAttributeType(
            new Type\MultiEnumAttributeType()
        );

        /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject $entityNameResolver */
        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(
                function ($entity, $format, $locale) {
                    return (string)$entity . '_' . $locale;
                }
            );

        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $manyToManyAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 505, 'fieldName' => 'names']);
        $manyToManyAttribute->setEntity(new EntityConfigModel());

        $manyToManyAttributeLocalizable = clone $manyToManyAttribute;
        $manyToManyAttributeLocalizable->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);

        $manyToManySearchAttributeType = new SearchableType\ManyToManySearchableAttributeType(
            new Type\ManyToManyAttributeType($entityNameResolver, $doctrineHelper)
        );

        $manyToOneAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 606, 'fieldName' => 'manytoone']);
        $manyToOneAttribute->setEntity(new EntityConfigModel());
        $manyToOneSearchAttributeType = new SearchableType\ManyToOneSearchableAttributeType(
            new Type\ManyToOneAttributeType($entityNameResolver, $doctrineHelper)
        );

        $fileAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 707, 'fieldName' => 'image']);
        $fileAttribute->setEntity(new EntityConfigModel());

        $fileSearchAttributeType = new SearchableType\FileSearchableAttributeType(new Type\FileAttributeType('file'));

        return [
            'not active attribute' => [
                'attribute' => $stringAttribute,
                'attributeType' => $stringSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_DELETE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true]),
                'hasAssociation' => true,
            ],
            'no attribute type' => [
                'attribute' => $stringAttribute,
                'attributeType' => null,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true]),
                'hasAssociation' => true,
            ],
            'attribute not filterable and not sortable' => [
                'attribute' => $stringAttribute,
                'attributeType' => $stringSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => false, 'sortable' => false]),
            ],
            'attribute filterable and not sortable' => [
                'attribute' => $stringAttribute,
                'attributeType' => $stringSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'fuzzy_search', 'sortable' => false]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'sku' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_STRING,
                                'data_name' => Query::TYPE_TEXT . '.sku',
                                'label' => self::LABEL,
                                'max_length' => 255
                            ]
                        ]
                    ]
                ],
            ],
            'attribute not filterable and sortable' => [
                'attribute' => $stringAttribute,
                'attributeType' => $stringSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => false, 'sortable' => true]),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'columns' => [
                        'sku' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'sku' => [
                                'data_name' => Query::TYPE_TEXT . '.sku',
                            ]
                        ]
                    ]
                ],
            ],
            'attribute filterable and sortable' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => StubEnumValue::class
                            ]
                        ]
                    ],
                    'columns' => [
                        'internalStatus_priority' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'internalStatus_priority' => [
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_priority',
                            ]
                        ]
                    ]
                ],
            ],
            'attribute filterable and sortable, limit without product family' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => true,
                'aggregatedData' => [$enumAttribute->getId() + 3000 => \random_int(1, 1000)],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => []
                    ],
                    'columns' => [
                        'internalStatus_priority' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => []
                    ]
                ],
            ],
            'attribute filterable and sortable, limit without product family, but filter state' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => true,
                'aggregatedData' => [$enumAttribute->getId() + 3000 => \random_int(1, 1000)],
                'filtersState' => ['internalStatus' => ['state']],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => StubEnumValue::class
                            ]
                        ]
                    ],
                    'columns' => [
                        'internalStatus_priority' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => []
                    ]
                ],
            ],
            'attribute filterable and sortable, limit without product family, but sorter state' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => true,
                'aggregatedData' => [$enumAttribute->getId() + 3000 => \random_int(1, 1000)],
                'filtersState' => [],
                'sortersState' => ['internalStatus_priority' => ['state']],
                'expected' => [
                    'filters' => [
                        'columns' => []
                    ],
                    'columns' => [
                        'internalStatus_priority' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'internalStatus_priority' => [
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_priority',
                            ]
                        ]
                    ]
                ],
            ],
            'attribute filterable and sortable, limit with product family' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => true,
                'aggregatedData' => [$enumAttribute->getId() + 2000 => \random_int(1, 1000)],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => StubEnumValue::class
                            ]
                        ]
                    ],
                    'columns' => [
                        'internalStatus_priority' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'internalStatus_priority' => [
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_priority',
                            ]
                        ]
                    ]
                ],
            ],
            'decimal attribute filterable and sortable' => [
                'attribute' => $decimalAttribute,
                'attributeType' => $decimalSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => false,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'weight' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_NUMBER_RANGE,
                                'data_name' => Query::TYPE_DECIMAL . '.weight',
                                'force_like' => true,
                                'label' => self::LABEL,
                                'options' => [
                                    'data_type' => NumberFilterTypeInterface::DATA_DECIMAL
                                ]
                            ]
                        ]
                    ],
                    'columns' => [
                        'weight' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'weight' => [
                                'data_name' => Query::TYPE_DECIMAL . '.weight',
                            ]
                        ]
                    ]
                ],
            ],
            'multi-enum attribute filterable and not sortable' => [
                'attribute' => $multiEnumAttribute,
                'attributeType' => $multiEnumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => false]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => StubEnumValue::class
                            ]
                        ]
                    ],
                ],
            ],
            'multi-enum attribute filterable and not sortable (no association mapping)' => [
                'attribute' => $multiEnumAttribute,
                'attributeType' => $multiEnumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => false]
                ),
                'hasAssociation' => false,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => null
                            ]
                        ]
                    ],
                ],
            ],
            'attribute filterable and sortable (localized)' => [
                'attribute' => $manyToManyAttributeLocalizable,
                'attributeType' => $manyToManySearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true]),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'names' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_STRING,
                                'data_name' => Query::TYPE_TEXT . '.names_' . LocalizationIdPlaceholder::NAME,
                                'label' => self::LABEL,
                                'max_length' => 255
                            ]
                        ]
                    ],
                    'columns' => [
                        'names' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'names' => [
                                'data_name' => Query::TYPE_TEXT . '.names_' . LocalizationIdPlaceholder::NAME,
                            ]
                        ]
                    ]
                ],
            ],
            'many-to-one attribute filterable and sortable' => [
                'attribute' => $manyToOneAttribute,
                'attributeType' => $manyToOneSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true]),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => [],
                'filtersState' => [],
                'sortersState' => [],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'manytoone' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_ENTITY,
                                'data_name' => Query::TYPE_INTEGER . '.manytoone',
                                'label' => self::LABEL,
                                'class' => StubEnumValue::class,
                            ]
                        ]
                    ],
                    'columns' => [
                        'manytoone' => [
                            'label' => self::LABEL,
                        ]
                    ],
                    'sorters' => [
                        'columns' => [
                            'manytoone' => [
                                'data_name' => Query::TYPE_TEXT . '.manytoone_' . LocalizationIdPlaceholder::NAME,
                            ]
                        ]
                    ]
                ],
            ],
            'attribute not filterable and not sortable, but incorrect data' => [
                'attribute' => $fileAttribute,
                'attributeType' => $fileSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true]),
            ],
        ];
    }

    /**
     * @param array $values
     * @return ConfigInterface
     */
    protected function getConfig(array $values = [])
    {
        /** @var ConfigIdInterface $id */
        $id = $this->createMock(ConfigIdInterface::class);

        return new Config($id, $values);
    }
}
