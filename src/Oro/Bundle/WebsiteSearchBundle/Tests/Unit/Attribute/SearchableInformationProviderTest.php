<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Attribute;

use Oro\Bundle\CMSBundle\Attribute\Type\WYSIWYGAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\BooleanAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\EnumAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\ManyToManyAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\ManyToOneAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\MultiEnumAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\OneToManyAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\TextAttributeType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Attribute\SearchableInformationProvider;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\BooleanSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\EnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\ManyToManySearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\ManyToOneSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\MultiEnumSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\OneToManySearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\StringSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\TextSearchableAttributeType;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\WYSIWYGSearchableAttributeType;
use Oro\Component\Testing\Unit\EntityTrait;

class SearchableInformationProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var SearchableInformationProvider */
    private $provider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new SearchableInformationProvider($this->configManager);
    }

    /**
     * @dataProvider getSearchableFieldNameDataProvider
     */
    public function testGetSearchableFieldName(
        FieldConfigModel $attribute,
        SearchAttributeTypeInterface $attributeType,
        string $expectedFieldName
    ): void {
        $boostedFieldName = $this->provider->getSearchableFieldName($attribute, $attributeType);

        self::assertEquals($expectedFieldName, $boostedFieldName);
    }

    public function getSearchableFieldNameDataProvider(): array
    {
        return [
            'enum attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new EnumSearchableAttributeType(new EnumAttributeType()),
                'expectedFieldName' => 'simple_filed_searchable',
            ],
            'multi enum attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new MultiEnumSearchableAttributeType(new MultiEnumAttributeType()),
                'expectedFieldName' => 'simple_filed_searchable',
            ],
            'many_to_one attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new ManyToOneSearchableAttributeType(
                    $this->createMock(ManyToOneAttributeType::class)
                ),
                'expectedFieldName' => 'simple_filed_LOCALIZATION_ID',
            ],
            'one_to_many attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new OneToManySearchableAttributeType(
                    $this->createMock(OneToManyAttributeType::class)
                ),
                'expectedFieldName' => 'simple_filed_LOCALIZATION_ID',
            ],
            'many_to_many attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new ManyToManySearchableAttributeType(
                    $this->createMock(ManyToManyAttributeType::class)
                ),
                'expectedFieldName' => 'simple_filed_LOCALIZATION_ID',
            ],
            'text attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new TextSearchableAttributeType(new TextAttributeType()),
                'expectedFieldName' => 'simple_filed',
            ],
            'string attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new StringSearchableAttributeType(new StringAttributeType()),
                'expectedFieldName' => 'simple_filed',
            ],
            'WYSIWYG attribute' => [
                'attribute'         => new FieldConfigModel('simple_filed', 'string'),
                'attributeType'     => new WYSIWYGSearchableAttributeType(
                    $this->createMock(WYSIWYGAttributeType::class)
                ),
                'expectedFieldName' => 'simple_filed',
            ],
        ];
    }

    public function testGetSearchableFieldNameNotSupported(): void
    {
        $attribute = new FieldConfigModel('simple_filed', 'string');
        $attributeType = new BooleanSearchableAttributeType(new BooleanAttributeType());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('Type %s is not supported', BooleanSearchableAttributeType::class));

        $this->provider->getSearchableFieldName($attribute, $attributeType);
    }

    public function testGetAttributeSearchBoost(): void
    {
        $attribute = new FieldConfigModel('searchable_field', 'string');
        $attribute->setEntity(new EntityConfigModel(Product::class));

        $configProvider = new ConfigProviderMock($this->configManager, 'attribute');
        $configProvider->addFieldConfig(
            Product::class,
            'searchable_field',
            'string',
            ['search_boost' => 1.35]
        );

        $this->configManager
            ->expects(self::once())
            ->method('getProvider')
            ->with('attribute')
            ->willReturn($configProvider);

        self::assertEquals(1.35, $this->provider->getAttributeSearchBoost($attribute));
    }
}
