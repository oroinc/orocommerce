<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductEntityAliasProvider;

class ProductEntityAliasProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DuplicateEntityAliasResolver */
    private $duplicateResolver;

    /** @var ProductEntityAliasProvider */
    private $entityAliasProvider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->duplicateResolver = $this->createMock(DuplicateEntityAliasResolver::class);

        $this->entityAliasProvider = new ProductEntityAliasProvider(
            $this->configManager,
            $this->duplicateResolver
        );
    }

    /**
     * @dataProvider getPossibleClassNames
     */
    public function testGetEntityAliasForProductAttribute($className, $attributeName, $expectedAlias, $expectedPlural)
    {
        $attributeFieldConfig = new Config(
            new FieldConfigId('attribute', Product::class, $attributeName, 'enum'),
            ['is_attribute' => true]
        );
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([$attributeFieldConfig]);
        $extendFieldConfig = new Config(
            new FieldConfigId('extend', Product::class, $attributeName, 'enum'),
            ['target_entity' => $className]
        );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', Product::class, $attributeName)
            ->willReturn($extendFieldConfig);
        $enumEntityConfig = new Config(
            new EntityConfigId('enum', Product::class),
            ['code' => 'test']
        );
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $className)
            ->willReturn($enumEntityConfig);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($className)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::once())
            ->method('hasAlias')
            ->with($expectedAlias, $expectedPlural)
            ->willReturn(false);
        $this->duplicateResolver->expects(self::never())
            ->method('getUniqueAlias');
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($className, new EntityAlias($expectedAlias, $expectedPlural));

        $entityAlias = $this->entityAliasProvider->getEntityAlias($className);
        $this->assertNotNull($entityAlias);
        $this->assertEquals($expectedAlias, $entityAlias->getAlias());
        $this->assertEquals($expectedPlural, $entityAlias->getPluralAlias());
    }

    /**
     * @return array
     */
    public function getPossibleClassNames()
    {
        return [
            [
                'Extend\Entity\EV_Product_New_Attribute_8fde6396',
                'new_attribute',
                'productnewattribute',
                'productnewattributes',
            ],
            [
                'Extend\Entity\EV_Product_New_Attribute_8fde6396',
                'newAttribute',
                'productnewattribute',
                'productnewattributes',
            ],
            [
                'Extend\Entity\EV_Product__My__Test_Attribute_8fde6396',
                'My_testAttribute',
                'productmytestattribute',
                'productmytestattributes',
            ],
            [
                'Extend\Entity\EV_Product_Product_8fde6396',
                'product',
                'productproduct',
                'productproducts',
            ],
        ];
    }

    public function testGetEntityAliasForNotProductRelatedEntity()
    {
        $className = 'Extend\Entity\EV_Category__My__Test__Attribute_8fde6396';

        $this->configManager->expects(self::never())
            ->method('getConfigs');
        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $entityAlias = $this->entityAliasProvider->getEntityAlias($className);
        $this->assertNull($entityAlias);
    }

    public function testGetEntityAliasForNotProductAttribute()
    {
        $className = 'Extend\Entity\EV_Product__My__Test__Entity_8fde6396';

        $attributeFieldConfig = new Config(
            new FieldConfigId('attribute', Product::class, 'My_Test_Field', 'enum'),
            ['is_attribute' => false]
        );
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([$attributeFieldConfig]);
        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $entityAlias = $this->entityAliasProvider->getEntityAlias($className);
        $this->assertNull($entityAlias);
    }

    public function testGetEntityAliasForProductAttributeWhenAliasAlreadyExistInEntityConfig()
    {
        $className = 'Extend\Entity\EV_Product_New_Attribute_8fde6396';
        $attributeName = 'new_attribute';
        $alias = 'productnewattribute';
        $pluralAlias = 'productnewattributes';

        $attributeFieldConfig = new Config(
            new FieldConfigId('attribute', Product::class, $attributeName, 'enum'),
            ['is_attribute' => true]
        );
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([$attributeFieldConfig]);
        $extendFieldConfig = new Config(
            new FieldConfigId('extend', Product::class, $attributeName, 'enum'),
            ['target_entity' => $className]
        );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', Product::class, $attributeName)
            ->willReturn($extendFieldConfig);
        $enumEntityConfig = new Config(
            new EntityConfigId('enum', Product::class),
            ['code' => 'test']
        );
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $className)
            ->willReturn($enumEntityConfig);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($className)
            ->willReturn(new EntityAlias($alias, $pluralAlias));
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $entityAlias = $this->entityAliasProvider->getEntityAlias($className);
        $this->assertNotNull($entityAlias);
        $this->assertEquals($alias, $entityAlias->getAlias());
        $this->assertEquals($pluralAlias, $entityAlias->getPluralAlias());
    }

    public function testGetEntityAliasForProductAttributeWhenAliasIsDuplicated()
    {
        $className = 'Extend\Entity\EV_Product_New_Attribute_8fde6396';
        $attributeName = 'new_attribute';
        $defaultAlias = 'productnewattribute';
        $defaultPluralAlias = 'productnewattributes';
        $expectedAlias = 'productnewattribute1';
        $expectedPluralAlias = $expectedAlias;

        $attributeFieldConfig = new Config(
            new FieldConfigId('attribute', Product::class, $attributeName, 'enum'),
            ['is_attribute' => true]
        );
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('attribute', Product::class, true)
            ->willReturn([$attributeFieldConfig]);
        $extendFieldConfig = new Config(
            new FieldConfigId('extend', Product::class, $attributeName, 'enum'),
            ['target_entity' => $className]
        );
        $this->configManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('extend', Product::class, $attributeName)
            ->willReturn($extendFieldConfig);
        $enumEntityConfig = new Config(
            new EntityConfigId('enum', Product::class),
            ['code' => 'test']
        );
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $className)
            ->willReturn($enumEntityConfig);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($className)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::once())
            ->method('hasAlias')
            ->with($defaultAlias, $defaultPluralAlias)
            ->willReturn(true);
        $this->duplicateResolver->expects(self::once())
            ->method('getUniqueAlias')
            ->with($defaultAlias, $defaultPluralAlias)
            ->willReturn($expectedAlias);
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($className, new EntityAlias($expectedAlias, $expectedPluralAlias));

        $entityAlias = $this->entityAliasProvider->getEntityAlias($className);
        $this->assertNotNull($entityAlias);
        $this->assertEquals($expectedAlias, $entityAlias->getAlias());
        $this->assertEquals($expectedPluralAlias, $entityAlias->getPluralAlias());
    }
}
