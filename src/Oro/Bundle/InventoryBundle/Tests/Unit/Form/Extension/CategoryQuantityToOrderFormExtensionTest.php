<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryQuantityToOrderFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;

class CategoryQuantityToOrderFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryQuantityToOrderFormExtension
     */
    protected $categoryFormExtension;

    protected function setUp()
    {
        $this->categoryFormExtension = new CategoryQuantityToOrderFormExtension();
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->getMock(FormBuilderInterface::class);
        $category = new CategoryStub();
        $builder->expects($this->once())
            ->method('getData')
            ->willReturn($category);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturn($builder);

        $options = [];
        $this->categoryFormExtension->buildForm($builder, $options);

        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getMinimumQuantityToOrder());
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getMaximumQuantityToOrder());
        $this->assertEquals(
            SystemConfigFallbackProvider::FALLBACK_ID,
            $category->getMinimumQuantityToOrder()->getFallback()
        );
        $this->assertEquals(
            SystemConfigFallbackProvider::FALLBACK_ID,
            $category->getMaximumQuantityToOrder()->getFallback()
        );
    }
}
