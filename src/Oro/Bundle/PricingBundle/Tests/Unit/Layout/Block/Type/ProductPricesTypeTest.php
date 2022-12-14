<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeRenderRegistry;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\PricingBundle\Layout\Block\Type\ProductPricesType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Symfony\Component\ExpressionLanguage\Expression;

class ProductPricesTypeTest extends BlockTypeTestCase
{
    /** @var AttributeRenderRegistry */
    private $attributeRenderRegistry;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $this->attributeRenderRegistry = new AttributeRenderRegistry();

        $this->attributeManager = $this->createMock(AttributeManager::class);

        $layoutFactoryBuilder->addType(
            new ProductPricesType($this->attributeRenderRegistry, $this->attributeManager)
        );

        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
    }

    public function testGetBlockView()
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('prices');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup);

        $attribute = new FieldConfigModel('attribute');

        $product = new Product();
        $pricesExpression = new Expression('context["productPrices"]');

        $this->assertFalse($this->attributeRenderRegistry->isAttributeRendered($attributeFamily, 'attribute'));

        $this->attributeManager->expects($this->once())
            ->method('getAttributeByFamilyAndName')
            ->with($attributeFamily, PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES)
            ->willReturn($attribute);

        $view = $this->getBlockView(
            ProductPricesType::NAME,
            [
                'productPrices' => $pricesExpression,
                'attributeFamily' => $attributeFamily,
                'product' => $product,
                'isPriceUnitsVisible' => false
            ]
        );

        $this->assertEquals($pricesExpression, $view->vars['productPrices']);
        $this->assertFalse($view->vars['isPriceUnitsVisible']);
        $this->assertEquals($product, $view->vars['product']);

        $this->assertTrue($this->attributeRenderRegistry->isAttributeRendered($attributeFamily, 'attribute'));
    }

    public function testGetBlockViewWithoutAttributeFamily()
    {
        $pricesExpression = new Expression('context["productPrices"]');

        $view = $this->getBlockView(
            ProductPricesType::NAME,
            [
                'productPrices' => $pricesExpression,
                'isPriceUnitsVisible' => false
            ]
        );

        $this->assertEquals($pricesExpression, $view->vars['productPrices']);
        $this->assertFalse($view->vars['isPriceUnitsVisible']);
        $this->assertNull($view->vars['product']);
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ProductPricesType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
