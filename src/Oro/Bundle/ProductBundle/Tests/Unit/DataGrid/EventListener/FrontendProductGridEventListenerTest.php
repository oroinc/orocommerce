<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterTypeInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendProductGridEventListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type as SearchableType;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

class FrontendProductGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    const LABEL = 'oro.test.label';

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeManager;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeTypeRegistry;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeConfigProvider;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    /** @var FrontendProductGridEventListener */
    protected $listener;

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

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['entity', $entityConfigProvider],
                    ['extend', $this->extendConfigProvider],
                    ['attribute', $this->attributeConfigProvider],
                ]
            );

        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->metadata->expects($this->any())
            ->method('getAssociationMapping')
            ->willReturn(['targetEntity' => StubEnumValue::class]);

        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn($this->metadata);

        $this->listener = new FrontendProductGridEventListener(
            $this->attributeManager,
            $this->attributeTypeRegistry,
            new AttributeConfigurationProvider($configManager),
            $doctrineHelper
        );
    }

    /**
     * @dataProvider onPreBuildDataProvider
     *
     * @param FieldConfigModel $attribute
     * @param $attributeType
     * @param ConfigInterface $extendConfig
     * @param ConfigInterface $attributeConfig
     * @param bool $hasAssociation
     * @param array $expected
     */
    public function testOnPreBuild(
        FieldConfigModel $attribute,
        $attributeType,
        ConfigInterface $extendConfig,
        ConfigInterface $attributeConfig,
        $hasAssociation = true,
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

        $config = DatagridConfiguration::create([]);

        $event = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals($expected, $config->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function onPreBuildDataProvider()
    {
        $stringAttribute = new FieldConfigModel('sku');
        $stringAttribute->setEntity(new EntityConfigModel());
        $stringSearchAttributeType = new SearchableType\StringSearchableAttributeType(new Type\StringAttributeType());

        $enumAttribute = new FieldConfigModel('internalStatus');
        $enumAttribute->setEntity(new EntityConfigModel())
            ->fromArray('extend', ['target_entity' => StubEnumValue::class]);
        $enumSearchAttributeType = new SearchableType\EnumSearchableAttributeType(new Type\EnumAttributeType());

        $decimalAttribute = new FieldConfigModel('weight');
        $decimalAttribute->setEntity(new EntityConfigModel());
        $decimalSearchAttributeType = new SearchableType\DecimalSearchableAttributeType(
            new Type\DecimalAttributeType('decimal')
        );

        $multiEnumAttribute = new FieldConfigModel('internalStatus');
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

        $manyToManyAttribute = new FieldConfigModel('names');
        $manyToManyAttribute->setEntity(new EntityConfigModel());

        $manyToManyAttributeLocalizable = clone $manyToManyAttribute;
        $manyToManyAttributeLocalizable->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);

        $manyToManySearchAttributeType = new SearchableType\ManyToManySearchableAttributeType(
            new Type\ManyToManyAttributeType($entityNameResolver, $doctrineHelper)
        );

        $manyToOneAttribute = new FieldConfigModel('manytoone');
        $manyToOneAttribute->setEntity(new EntityConfigModel());
        $manyToOneSearchAttributeType = new SearchableType\ManyToOneSearchableAttributeType(
            new Type\ManyToOneAttributeType($entityNameResolver, $doctrineHelper)
        );

        $fileAttribute = new FieldConfigModel('image');
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'sku' => [
                                'type' => SearchableType\SearchableAttributeTypeInterface::FILTER_TYPE_STRING,
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchableAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'weight' => [
                                'type' => SearchableType\SearchableAttributeTypeInterface::FILTER_TYPE_NUMBER_RANGE,
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchableAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'internalStatus' => [
                                'type' => SearchableType\SearchableAttributeTypeInterface::FILTER_TYPE_MULTI_ENUM,
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'names' => [
                                'type' => SearchableType\SearchableAttributeTypeInterface::FILTER_TYPE_STRING,
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
                'expected' => [
                    'filters' => [
                        'columns' => [
                            'manytoone' => [
                                'type' => SearchableType\SearchableAttributeTypeInterface::FILTER_TYPE_ENTITY,
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
                'hasAssociation' => true,
                'expected' => [],
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
