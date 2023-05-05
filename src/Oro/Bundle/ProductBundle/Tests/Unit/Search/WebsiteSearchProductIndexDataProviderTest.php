<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Search;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeConfigurationProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\EnumAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\ManyToManyAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\MultiEnumAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataModel;
use Oro\Bundle\ProductBundle\Search\ProductIndexFieldsProvider;
use Oro\Bundle\ProductBundle\Search\WebsiteSearchProductIndexDataProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\WebsiteSearchBundle\Attribute\SearchableInformationProvider;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\EnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\ManyToManySearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\MultiEnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\StringSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchProductIndexDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeTypeRegistry;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeConfigProvider;

    /** @var ProductIndexFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $filterableAttributeProvider;

    /** @var WebsiteSearchProductIndexDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);

        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->willReturnMap([
                ['extend', $this->extendConfigProvider],
                ['attribute', $this->attributeConfigProvider],
            ]);

        $this->filterableAttributeProvider = new ProductIndexFieldsProvider();
        $searchableProvider = new SearchableInformationProvider($configManager);

        $this->provider = new WebsiteSearchProductIndexDataProvider(
            $this->attributeTypeRegistry,
            new AttributeConfigurationProvider($configManager),
            $this->filterableAttributeProvider,
            PropertyAccess::createPropertyAccessor(),
            $searchableProvider
        );
    }

    /**
     * @dataProvider getIndexDataProvider
     */
    public function testGetIndexData(
        FieldConfigModel $attribute,
        ?AttributeTypeInterface $attributeType,
        ConfigInterface $extendConfig,
        ConfigInterface $attributeConfig,
        array $expected = []
    ) {
        $locale1 = $this->getEntity(Localization::class, ['id' => 1001]);
        $locale2 = $this->getEntity(Localization::class, ['id' => 1002]);

        $product = $this->getEntity(
            Product::class,
            [
                'inventoryStatus' => $this->getEnumValue(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock', 42),
                'sku' => 'SKU123',
                'descriptions' => new ArrayCollection(
                    [
                        $this->getLocalizedValue('default description'),
                        $this->getLocalizedValue('locale1 description', $locale1),
                        $this->getLocalizedValue('locale2 description', $locale2),
                    ]
                ),
                'flags' => new ArrayCollection(
                    [
                        $this->getEnumValue('bestseller', 'Best Sales', 105),
                        $this->getEnumValue('discounts', 'New Discounts', 110)
                    ]
                ),
            ]
        );

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

        $data = $this->provider->getIndexData($product, $attribute, [$locale1, $locale2]);
        $this->assertEquals($expected, array_values($data->getArrayCopy()));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getIndexDataProvider(): array
    {
        $enumAttribute = new FieldConfigModel('inventoryStatus');
        $enumAttribute->setEntity(new EntityConfigModel(Product::class));
        $enumAttributeType = new EnumAttributeType();
        $enumSearchAttributeType = new EnumSearchableAttributeType($enumAttributeType);

        $stringAttribute = new FieldConfigModel('sku');
        $stringAttribute->setEntity(new EntityConfigModel(Product::class));
        $stringAttributeType = new StringAttributeType();
        $stringSearchAttributeType = new StringSearchableAttributeType($stringAttributeType);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(function ($entity, $format, $locale) {
                return (string)$entity . '_' . $locale;
            });

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $manyToManyAttribute = new FieldConfigModel('descriptions');
        $manyToManyAttribute->setEntity(new EntityConfigModel(Product::class))
            ->fromArray('extend', ['target_entity' => LocalizedFallbackValue::class]);
        $manyToManyAttributeType = new ManyToManyAttributeType($entityNameResolver, $doctrineHelper);
        $manyToManySearchAttributeType = new ManyToManySearchableAttributeType($manyToManyAttributeType);

        $multiEnumAttribute = new FieldConfigModel('flags');
        $multiEnumAttribute->setEntity(new EntityConfigModel(Product::class));
        $multiEnumAttributeType = new MultiEnumAttributeType();
        $multiEnumSearchAttributeType = new MultiEnumSearchableAttributeType($multiEnumAttributeType);

        return [
            'not active' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_DELETE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true, 'searchable' => true]),
                'expected' => [],
            ],
            'no attribute type' => [
                'attribute' => $enumAttribute,
                'attributeType' => null,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true, 'searchable' => true]),
                'expected' => [],
            ],
            'not filterable, not sortable, not searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => false, 'sortable' => false, 'searchable' => false]
                ),
                'expected' => [],
            ],
            'filterable, not sortable, not searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => false, 'searchable' => false]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'inventoryStatus_enum.' . Product::INVENTORY_STATUS_IN_STOCK,
                        1,
                        [],
                        false,
                        false
                    ),
                ],
            ],
            'not filterable, sortable, not searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => false, 'sortable' => true, 'searchable' => false]
                ),
                'expected' => [
                    new ProductIndexDataModel('inventoryStatus_priority', 42, [], false, false),
                ],
            ],
            'not filterable, not sortable, searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => false, 'sortable' => false, 'searchable' => true]
                ),
                'expected' => [
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'In Stock', [], false, true),
                ],
            ],
            'filterable, sortable, not searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => true, 'searchable' => false]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'inventoryStatus_enum.' . Product::INVENTORY_STATUS_IN_STOCK,
                        1,
                        [],
                        false,
                        false
                    ),
                    new ProductIndexDataModel('inventoryStatus_priority', 42, [], false, false),
                ],
            ],
            'filterable, not sortable, searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => false, 'searchable' => true]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'inventoryStatus_enum.' . Product::INVENTORY_STATUS_IN_STOCK,
                        1,
                        [],
                        false,
                        false
                    ),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'In Stock', [], false, true),
                ],
            ],
            'not filterable, sortable, searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => false, 'sortable' => true, 'searchable' => true]
                ),
                'expected' => [
                    new ProductIndexDataModel('inventoryStatus_priority', 42, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'In Stock', [], false, true),
                ],
            ],
            'filterable, sortable, searchable, not localized' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(['filterable' => true, 'sortable' => true, 'searchable' => true]),
                'expected' => [
                    new ProductIndexDataModel(
                        'inventoryStatus_enum.' . Product::INVENTORY_STATUS_IN_STOCK,
                        1,
                        [],
                        false,
                        false
                    ),
                    new ProductIndexDataModel('inventoryStatus_priority', 42, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'In Stock', [], false, true),
                ],
            ],
            'filterable equal sortable, not searchable, not localized' => [
                'attribute' => $stringAttribute,
                'attributeType' => $stringSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => true, 'searchable' => false]
                ),
                'expected' => [
                    new ProductIndexDataModel('sku', 'SKU123', [], false, false),
                ],
            ],
            'filterable, sortable, searchable, localized' => [
                'attribute' => $manyToManyAttribute,
                'attributeType' => $manyToManySearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => true, 'searchable' => true]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'descriptions_LOCALIZATION_ID',
                        'locale1 description',
                        [LocalizationIdPlaceholder::NAME => 1001],
                        true,
                        false
                    ),
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'locale1 description',
                        [LocalizationIdPlaceholder::NAME => 1001],
                        true,
                        true
                    ),
                    new ProductIndexDataModel(
                        'descriptions_LOCALIZATION_ID',
                        'locale2 description',
                        [LocalizationIdPlaceholder::NAME => 1002],
                        true,
                        false
                    ),
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'locale2 description',
                        [LocalizationIdPlaceholder::NAME => 1002],
                        true,
                        true
                    ),
                ],
            ],
            'filterable, not sortable, searchable, not localized (multi enum)' => [
                'attribute' => $multiEnumAttribute,
                'attributeType' => $multiEnumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => false, 'searchable' => true]
                ),
                'expected' => [
                    new ProductIndexDataModel('flags_enum.bestseller', 1, [], false, false),
                    new ProductIndexDataModel('flags_enum.discounts', 1, [], false, false),
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'Best Sales New Discounts',
                        [],
                        false,
                        true
                    ),
                ],
            ],
            'filterable, sortable, searchable, not localized, with not empty boost' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => true, 'searchable' => true, 'search_boost' => 1.0]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'inventoryStatus_enum.' . Product::INVENTORY_STATUS_IN_STOCK,
                        1,
                        [],
                        false,
                        false
                    ),
                    new ProductIndexDataModel('inventoryStatus_priority', 42, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'In Stock', [], false, true),
                    new ProductIndexDataModel('inventoryStatus_searchable', 'In Stock', [], false, false),
                ],
            ],
            'filterable, sortable, searchable, not localized, with 0 boost' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => true, 'searchable' => true, 'search_boost' => 0.0]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'inventoryStatus_enum.' . Product::INVENTORY_STATUS_IN_STOCK,
                        1,
                        [],
                        false,
                        false
                    ),
                    new ProductIndexDataModel('inventoryStatus_priority', 42, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'In Stock', [], false, true),
                ],
            ],
            'filterable, sortable, searchable, not localized, with null boost' => [
                'attribute' => $enumAttribute,
                'attributeType' => $enumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => true, 'searchable' => true, 'search_boost' => null]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'inventoryStatus_enum.' . Product::INVENTORY_STATUS_IN_STOCK,
                        1,
                        [],
                        false,
                        false
                    ),
                    new ProductIndexDataModel('inventoryStatus_priority', 42, [], false, false),
                    new ProductIndexDataModel(IndexDataProvider::ALL_TEXT_L10N_FIELD, 'In Stock', [], false, true),
                ],
            ],
            'filterable, not sortable, searchable, not localized (multi enum), with boost' => [
                'attribute' => $multiEnumAttribute,
                'attributeType' => $multiEnumSearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => false, 'searchable' => true, 'search_boost' => 1.0]
                ),
                'expected' => [
                    new ProductIndexDataModel('flags_enum.bestseller', 1, [], false, false),
                    new ProductIndexDataModel('flags_enum.discounts', 1, [], false, false),
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'Best Sales New Discounts',
                        [],
                        false,
                        true
                    ),
                    new ProductIndexDataModel('flags_searchable', 'Best Sales New Discounts', [], false, false)
                ],
            ],
            'not filterable, not sortable, searchable, localized, with not empty boost' => [
                'attribute' => $manyToManyAttribute,
                'attributeType' => $manyToManySearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => false, 'sortable' => false, 'searchable' => true, 'search_boost' => 1.0]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'locale1 description',
                        [LocalizationIdPlaceholder::NAME => 1001],
                        true,
                        true
                    ),
                    new ProductIndexDataModel(
                        'descriptions_LOCALIZATION_ID',
                        'locale1 description',
                        [LocalizationIdPlaceholder::NAME => 1001],
                        true,
                        false
                    ),
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'locale2 description',
                        [LocalizationIdPlaceholder::NAME => 1002],
                        true,
                        true
                    ),
                    new ProductIndexDataModel(
                        'descriptions_LOCALIZATION_ID',
                        'locale2 description',
                        [LocalizationIdPlaceholder::NAME => 1002],
                        true,
                        false
                    ),
                ],
            ],
            'filterable, sortable, searchable, localized, with not empty boost (no duplicates)' => [
                'attribute' => $manyToManyAttribute,
                'attributeType' => $manyToManySearchAttributeType,
                'extendConfig' => $this->getConfig(['state' => ExtendScope::STATE_ACTIVE]),
                'attributeConfig' => $this->getConfig(
                    ['filterable' => true, 'sortable' => true, 'searchable' => true, 'search_boost' => 1.0]
                ),
                'expected' => [
                    new ProductIndexDataModel(
                        'descriptions_LOCALIZATION_ID',
                        'locale1 description',
                        [LocalizationIdPlaceholder::NAME => 1001],
                        true,
                        false
                    ),
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'locale1 description',
                        [LocalizationIdPlaceholder::NAME => 1001],
                        true,
                        true
                    ),
                    new ProductIndexDataModel(
                        'descriptions_LOCALIZATION_ID',
                        'locale2 description',
                        [LocalizationIdPlaceholder::NAME => 1002],
                        true,
                        false
                    ),
                    new ProductIndexDataModel(
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        'locale2 description',
                        [LocalizationIdPlaceholder::NAME => 1002],
                        true,
                        true
                    ),
                ],
            ],
        ];
    }

    private function getConfig(array $values = []): Config
    {
        return new Config($this->createMock(ConfigIdInterface::class), $values);
    }

    private function getEnumValue(string $id, string $name, int $priority): AbstractEnumValue
    {
        $enum = $this->createMock(AbstractEnumValue::class);
        $enum->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $enum->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $enum->expects($this->any())
            ->method('getPriority')
            ->willReturn($priority);

        return $enum;
    }

    private function getLocalizedValue(string $string, Localization $localization = null): ProductDescription
    {
        $value = new ProductDescription();
        $value->setLocalization($localization)->setString($string);

        return $value;
    }
}
