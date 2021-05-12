<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductQuantityToOrderFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;
use Symfony\Component\Form\FormBuilderInterface;

class ProductQuantityToOrderFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductQuantityToOrderFormExtension
     */
    protected $productFormExtension;

    protected function setUp(): void
    {
        $this->productFormExtension = new ProductQuantityToOrderFormExtension();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $product = new ProductStub();
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn($product);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturn($builder);

        $options = [];
        $this->productFormExtension->buildForm($builder, $options);

        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getMinimumQuantityToOrder());
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getMaximumQuantityToOrder());
        $this->assertEquals(
            CategoryFallbackProvider::FALLBACK_ID,
            $product->getMinimumQuantityToOrder()->getFallback()
        );
        $this->assertEquals(
            CategoryFallbackProvider::FALLBACK_ID,
            $product->getMaximumQuantityToOrder()->getFallback()
        );
    }
}
