<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductEntityAliasProvider;

class ProductEntityAliasProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $attributeConfigHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var ProductEntityAliasProvider */
    protected $entityAliasProvider;

    protected function setUp()
    {
        $this->attributeConfigHelper = $this->getMockBuilder(AttributeConfigHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityAliasProvider = new ProductEntityAliasProvider(
            $this->attributeConfigHelper,
            $this->configManager
        );
    }

    /**
     * @dataProvider getPossibleClassNames
     * @param string $className
     * @param boolean $isAttribute
     * @param array $attributesList
     * @param string $expectedAlias
     * @param string $expectedPlural
     */
    public function testGetEntityAlias($className, $isAttribute, $attributesList, $expectedAlias, $expectedPlural)
    {
        $this->setConfigManager($attributesList);
        $this->setAttributeConfigHelper($isAttribute);

        $entityAlias = $this->entityAliasProvider->getEntityAlias($className);

        if (!$isAttribute) {
            $this->assertNull($entityAlias);

            return;
        }

        $this->assertNotNull($entityAlias);
        $this->assertEquals($expectedAlias, $entityAlias->getAlias());
        $this->assertEquals($expectedPlural, $entityAlias->getPluralAlias());
    }

    /**
     * @param array $attributesList
     */
    protected function setConfigManager($attributesList)
    {
        $entityMetadata = new EntityMetadata(Product::class);
        $entityMetadata->propertyMetadata = array_flip($attributesList);

        $this->configManager->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn($entityMetadata);
    }

    /**
     * @param boolean $isAttribute
     */
    protected function setAttributeConfigHelper($isAttribute)
    {
        $this->attributeConfigHelper->expects($this->any())
            ->method('isFieldAttribute')
            ->willReturn($isAttribute);
    }

    /**
     * @return array
     */
    public function getPossibleClassNames()
    {
        return [
            [
                'Extend\Entity\EV_Product_New_Attribute_8fde6396',
                true,
                ['new_attribute'],
                'productnewattribute',
                'productnewattributes',
            ],
            [
                'Extend\Entity\EV_Product_New_Attribute_8fde6396',
                true,
                ['newAttribute'],
                'productnewattribute',
                'productnewattributes',
            ],
            [
                'Extend\Entity\EV_Product__My__Test__Attribute_8fde6396',
                false,
                ['My_Test_Attribute'],
                'productmytestattribute',
                'productmytestattributes',
            ],
            [
                'Extend\Entity\EV_Category__My__Test__Attribute_8fde6396',
                false,
                ['My_Test_Attribute'],
                null,
                null,
            ],
            [
                'Extend\Entity\EV_Product__My__Test_Attribute_8fde6396',
                true,
                ['My_testAttribute'],
                'productmytestattribute',
                'productmytestattributes',
            ],
            [
                'Extend\Entity\EV_Product_Product_8fde6396',
                true,
                ['product'],
                'productproduct',
                'productproducts',
            ],
        ];
    }
}
