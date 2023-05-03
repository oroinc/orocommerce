<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Provider\State\DatagridStateProviderInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
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
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendProductGridEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\FamilyAttributeCountsProvider;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type as SearchableType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class FrontendProductGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const LABEL = 'oro.test.label';
    private const LIMIT_FILTERS_SORTERS = 'oro_product.limit_filters_sorters_on_product_listing';
    private const DATAGRID_NAME = 'test_name';

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

    /** @var DatagridParametersHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridParametersHelper;

    /** @var FamilyAttributeCountsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $familyAttributeCountsProvider;

    /** @var FrontendProductGridEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);

        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->getConfig(['label' => self::LABEL]));

        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);

        $configManager = $this->createMock(EntityConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['entity', $entityConfigProvider],
                ['extend', $this->extendConfigProvider],
                ['attribute', $this->attributeConfigProvider],
            ]);

        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->metadata->expects($this->any())
            ->method('getAssociationMapping')
            ->willReturn(['targetEntity' => TestEnumValue::class]);

        $this->attributeFamilyRepository = $this->createMock(AttributeFamilyRepository::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn($this->metadata);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($this->attributeFamilyRepository);

        $this->datagridConfig = DatagridConfiguration::create(['name' => self::DATAGRID_NAME]);

        $this->datagrid = new Datagrid('datagrid', $this->datagridConfig, new ParameterBag([]));
        $this->datagrid->setAcceptor(new Acceptor());

        $this->filtersStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->sortersStateProvider = $this->createMock(DatagridStateProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->datagridParametersHelper = $this->createMock(DatagridParametersHelper::class);
        $this->familyAttributeCountsProvider = $this->createMock(FamilyAttributeCountsProvider::class);

        $this->listener = new FrontendProductGridEventListener(
            $this->attributeManager,
            $this->attributeTypeRegistry,
            new AttributeConfigurationProvider($configManager),
            $doctrineHelper,
            $this->configManager,
            $this->datagridParametersHelper,
            $this->familyAttributeCountsProvider
        );
    }

    /**
     * @dataProvider onPreBuildDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function testOnPreBuild(
        ?FieldConfigModel $attribute,
        ?SearchAttributeTypeInterface $attributeType,
        ConfigInterface $extendConfig,
        ConfigInterface $attributeConfig,
        bool $hasAssociation = true,
        bool $limitFiltersSorters = false,
        array $aggregatedData = [],
        array $expected = []
    ) {
        $attributes = $attribute && false === $limitFiltersSorters ? [$attribute] : [];

        $this->attributeManager->expects($this->any())
            ->method('getSortableOrFilterableAttributesByClass')
            ->willReturn($attributes);

        $this->attributeTypeRegistry->expects($this->any())
            ->method('getAttributeType')
            ->with($attribute)
            ->willReturn($attributeType);

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($extendConfig);
        $this->attributeConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($attributeConfig);

        $this->metadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturn($hasAssociation);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::LIMIT_FILTERS_SORTERS)
            ->willReturn($limitFiltersSorters);

        // Checks search query is executed not more than once.
        $this->familyAttributeCountsProvider->expects($this->atMost(1))
            ->method('getFamilyAttributeCounts')
            ->willReturn($aggregatedData);

        $this->datagridParametersHelper->expects($this->atLeastOnce())
            ->method('isDatagridExtensionSkipped')
            ->willReturn(false);

        $event = new PreBuild($this->datagridConfig, $this->datagrid->getParameters());

        $this->listener->onPreBuild($event);

        $this->assertEquals(array_merge(['name' => self::DATAGRID_NAME], $expected), $this->datagridConfig->toArray());

        $event = new PreBuild($this->datagridConfig, $this->datagrid->getParameters());

        $this->listener->onPreBuild($event);

        $this->assertEquals(array_merge(['name' => self::DATAGRID_NAME], $expected), $this->datagridConfig->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onPreBuildDataProvider(): array
    {
        $stringAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 101, 'fieldName' => 'sku']);
        $stringAttribute->setEntity(new EntityConfigModel(Product::class));
        $stringSearchAttributeType = new SearchableType\StringSearchableAttributeType(new Type\StringAttributeType());

        $enumAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 202, 'fieldName' => 'internalStatus']);
        $enumAttribute->setEntity(new EntityConfigModel(Product::class))
            ->fromArray('extend', ['target_entity' => TestEnumValue::class]);
        $enumSearchAttributeType = new SearchableType\EnumSearchableAttributeType(new Type\EnumAttributeType());

        $decimalAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 303, 'fieldName' => 'weight']);
        $decimalAttribute->setEntity(new EntityConfigModel(Product::class));
        $decimalSearchAttributeType = new SearchableType\DecimalSearchableAttributeType(
            new Type\DecimalAttributeType()
        );

        $multiEnumAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 404, 'fieldName' => 'internalStatus']);
        $multiEnumAttribute->setEntity(new EntityConfigModel(Product::class));
        $multiEnumSearchAttributeType = new SearchableType\MultiEnumSearchableAttributeType(
            new Type\MultiEnumAttributeType()
        );

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(function ($entity, $format, $locale) {
                return (string)$entity . '_' . $locale;
            });

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $manyToManyAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 505, 'fieldName' => 'names']);
        $manyToManyAttribute->setEntity(new EntityConfigModel(Product::class));

        $manyToManyAttributeLocalizable = clone $manyToManyAttribute;
        $manyToManyAttributeLocalizable->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);

        $manyToManySearchAttributeType = new SearchableType\ManyToManySearchableAttributeType(
            new Type\ManyToManyAttributeType($entityNameResolver, $doctrineHelper)
        );

        $manyToOneAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 606, 'fieldName' => 'manytoone']);
        $manyToOneAttribute->setEntity(new EntityConfigModel(Product::class));
        $manyToOneSearchAttributeType = new SearchableType\ManyToOneSearchableAttributeType(
            new Type\ManyToOneAttributeType($entityNameResolver, $doctrineHelper)
        );

        $fileAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 707, 'fieldName' => 'image']);
        $fileAttribute->setEntity(new EntityConfigModel(Product::class));

        $fileSearchAttributeType = new SearchableType\FileSearchableAttributeType(new Type\FileAttributeType());

        return [
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
                    ['filterable' => true, 'filter_by' => 'fulltext_search', 'sortable' => false]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => ['familyAttributesCount' => []],
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_enum.' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => TestEnumValue::class
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
                'aggregatedData' => [
                    'familyAttributesCount' => [$enumAttribute->getId() + 3000 => \random_int(1, 1000)]
                ],
                'expected' => [],
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
                'aggregatedData' => [
                    'familyAttributesCount' => [$enumAttribute->getId() + 3000 => \random_int(1, 1000)]
                ],
                'expected' => [],
            ],
            'attribute filterable and sortable, limit with product family' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => false,
                'aggregatedData' => [
                    'familyAttributesCount' => [$enumAttribute->getId() + 2000 => \random_int(1, 1000)]
                ],
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_enum.' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => TestEnumValue::class
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
            'attribute filterable and sortable, limit without product family, but sorter state' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'filter_by' => 'exact_value', 'sortable' => true]
                ),
                'hasAssociation' => true,
                'limitFiltersSorters' => true,
                'aggregatedData' => [
                    'familyAttributesCount' => [$enumAttribute->getId() + 3000 => \random_int(1, 1000)]
                ],
                'expected' => [],
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_enum.' . EnumIdPlaceholder::NAME,
                                'force_like' => true,
                                'label' => self::LABEL,
                                'class' => TestEnumValue::class
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
                                'data_name' => Query::TYPE_INTEGER . '.internalStatus_enum.' . EnumIdPlaceholder::NAME,
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'manytoone' => [
                                'type' => SearchableType\SearchAttributeTypeInterface::FILTER_TYPE_ENTITY,
                                'data_name' => Query::TYPE_INTEGER . '.manytoone',
                                'label' => self::LABEL,
                                'class' => TestEnumValue::class,
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
            ]
        ];
    }

    private function getConfig(array $values = []): ConfigInterface
    {
        return new Config($this->createMock(ConfigIdInterface::class), $values);
    }
}
