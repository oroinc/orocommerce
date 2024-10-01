<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductEntityAliasProvider;

class ProductEntityAliasProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DuplicateEntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $duplicateResolver;

    /** @var ProductEntityAliasProvider */
    private $entityAliasProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->duplicateResolver = $this->createMock(DuplicateEntityAliasResolver::class);

        $this->entityAliasProvider = new ProductEntityAliasProvider(
            $this->configManager,
            $this->duplicateResolver,
            (new InflectorFactory())->build()
        );
    }

    /**
     * @dataProvider getPossibleClassNames
     */
    public function testGetEntityAliasForProductAttribute(
        string $attributeEnumCode,
        string $attributeClassName,
        string $expectedAlias,
        string $expectedPluralAlias
    ): void {
        $attributeName = 'testAttr';
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([
                new Config(
                    new FieldConfigId('attribute', Product::class, $attributeName, 'enum'),
                    ['is_attribute' => true]
                )
            ]);
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('enum', Product::class, $attributeName)
            ->willReturn(new Config(
                new FieldConfigId('enum', Product::class, $attributeName, 'enum'),
                ['enum_code' => $attributeEnumCode]
            ));

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($attributeClassName)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::once())
            ->method('hasAlias')
            ->with($expectedAlias, $expectedPluralAlias)
            ->willReturn(false);
        $this->duplicateResolver->expects(self::never())
            ->method('getUniqueAlias');
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($attributeClassName, new EntityAlias($expectedAlias, $expectedPluralAlias));

        $entityAlias = $this->entityAliasProvider->getEntityAlias($attributeClassName);
        self::assertNotNull($entityAlias);
        self::assertEquals($expectedAlias, $entityAlias->getAlias());
        self::assertEquals($expectedPluralAlias, $entityAlias->getPluralAlias());
    }

    public static function getPossibleClassNames(): array
    {
        return [
            [
                'product_new_attribute_8fde6396',
                'Extend\Entity\EV_Product_New_Attribute_8fde6396',
                'extproductattributenewattribute',
                'extproductattributenewattributes'
            ],
            [
                'product_my__test__attribute_8fde6396',
                'Extend\Entity\EV_Product_My__Test__Attribute_8fde6396',
                'extproductattributemytestattribute',
                'extproductattributemytestattributes'
            ],
            [
                'product_product_8fde6396',
                'Extend\Entity\EV_Product_Product_8fde6396',
                'extproductattributeproduct',
                'extproductattributeproducts'
            ]
        ];
    }

    public function testGetEntityAliasForNotProductAttributeRelatedEntity(): void
    {
        $attributeClassName = 'Extend\Entity\EV_Category__My__Test__Attribute_8fde6396';

        $this->configManager->expects(self::never())
            ->method('getConfigs');
        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $entityAlias = $this->entityAliasProvider->getEntityAlias($attributeClassName);
        self::assertNull($entityAlias);
    }

    public function testGetEntityAliasForEnumEntityThatIsNotProductAttribute(): void
    {
        $attributeName = 'testAttr';
        $attributeClassName = 'Extend\Entity\EV_Product__My__Test__Entity_8fde6396';

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([
                new Config(
                    new FieldConfigId('attribute', Product::class, $attributeName, 'enum'),
                    ['is_attribute' => false]
                )
            ]);
        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $entityAlias = $this->entityAliasProvider->getEntityAlias($attributeClassName);
        self::assertNull($entityAlias);
    }

    public function testGetEntityAliasForProductAttributeWhenAliasAlreadyExistInEntityConfig(): void
    {
        $attributeName = 'testAttr';
        $attributeEnumCode = 'product_new_attribute_8fde6396';
        $attributeClassName = 'Extend\Entity\EV_Product_New_Attribute_8fde6396';
        $alias = 'productnewattribute';
        $pluralAlias = 'productnewattributes';

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([
                new Config(
                    new FieldConfigId('attribute', Product::class, $attributeName, 'enum'),
                    ['is_attribute' => true]
                )
            ]);
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('enum', Product::class, $attributeName)
            ->willReturn(new Config(
                new FieldConfigId('enum', Product::class, $attributeName, 'enum'),
                ['enum_code' => $attributeEnumCode]
            ));

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($attributeClassName)
            ->willReturn(new EntityAlias($alias, $pluralAlias));
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $entityAlias = $this->entityAliasProvider->getEntityAlias($attributeClassName);
        self::assertNotNull($entityAlias);
        self::assertEquals($alias, $entityAlias->getAlias());
        self::assertEquals($pluralAlias, $entityAlias->getPluralAlias());
    }

    public function testGetEntityAliasForProductAttributeWhenAliasIsDuplicated(): void
    {
        $attributeName = 'testAttr';
        $attributeEnumCode = 'product_new_attribute_8fde6396';
        $attributeClassName = 'Extend\Entity\EV_Product_New_Attribute_8fde6396';
        $defaultAlias = 'extproductattributenewattribute';
        $defaultPluralAlias = 'extproductattributenewattributes';
        $expectedAlias = 'extproductattributenewattribute1';
        $expectedPluralAlias = $expectedAlias . 's';

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([
                new Config(
                    new FieldConfigId('attribute', Product::class, $attributeName, 'enum'),
                    ['is_attribute' => true]
                )
            ]);
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('enum', Product::class, $attributeName)
            ->willReturn(new Config(
                new FieldConfigId('enum', Product::class, $attributeName, 'enum'),
                ['enum_code' => $attributeEnumCode]
            ));

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($attributeClassName)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::exactly(2))
            ->method('hasAlias')
            ->withConsecutive([$defaultAlias, $defaultPluralAlias], [$expectedAlias, $expectedPluralAlias])
            ->willReturnOnConsecutiveCalls(true, false);
        $this->duplicateResolver->expects(self::once())
            ->method('getUniqueAlias')
            ->with($defaultAlias, $defaultPluralAlias)
            ->willReturn($expectedAlias);
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($attributeClassName, new EntityAlias($expectedAlias, $expectedPluralAlias));

        $entityAlias = $this->entityAliasProvider->getEntityAlias($attributeClassName);
        self::assertNotNull($entityAlias);
        self::assertEquals($expectedAlias, $entityAlias->getAlias());
        self::assertEquals($expectedPluralAlias, $entityAlias->getPluralAlias());
    }
}
