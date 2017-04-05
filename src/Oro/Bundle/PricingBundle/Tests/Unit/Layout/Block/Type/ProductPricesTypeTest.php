<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\ExpressionLanguage\Expression;

use Oro\Bundle\PricingBundle\Layout\Block\Type\ProductPricesType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Layout\AttributeGroupRenderRegistry;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

class ProductPricesTypeTest extends BlockTypeTestCase
{
    /** @var AttributeGroupRenderRegistry */
    protected $attributeGroupRenderRegistry;

    /**
     * @param LayoutFactoryBuilderInterface $layoutFactoryBuilder
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $this->attributeGroupRenderRegistry = new AttributeGroupRenderRegistry();

        $ProductPricesType = new ProductPricesType($this->attributeGroupRenderRegistry);

        $layoutFactoryBuilder
            ->addType($ProductPricesType);

        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
    }

    public function testGetBlockView()
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setCode('prices');

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('family_code');
        $attributeFamily->addAttributeGroup($attributeGroup);

        $product = new Product();
        $pricesExpression = new Expression('context["productPrices"]');

        $this->assertFalse($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));

        $view = $this->getBlockView(
            ProductPricesType::NAME,
            [
                'productPrices' => $pricesExpression,
                'attributeFamily' => $attributeFamily,
                'product' => $product,
                'productUnitSelectionVisible' => false
            ]
        );

        $this->assertEquals($pricesExpression, $view->vars['productPrices']);
        $this->assertFalse($view->vars['productUnitSelectionVisible']);
        $this->assertEquals($product, $view->vars['product']);

        $this->assertTrue($this->attributeGroupRenderRegistry->isRendered($attributeFamily, $attributeGroup));
    }

    public function testGetBlockViewWithoutAttributeFamily()
    {
        $pricesExpression = new Expression('context["productPrices"]');

        $view = $this->getBlockView(
            ProductPricesType::NAME,
            [
                'productPrices' => $pricesExpression
            ]
        );

        $this->assertEquals($pricesExpression, $view->vars['productPrices']);
        $this->assertNull($view->vars['productUnitSelectionVisible']);
        $this->assertNull($view->vars['product']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(ProductPricesType::NAME);

        $this->assertSame(ProductPricesType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(ProductPricesType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
