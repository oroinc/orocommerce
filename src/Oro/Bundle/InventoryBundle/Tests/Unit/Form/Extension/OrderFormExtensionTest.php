<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\InventoryBundle\Entity\Helper\WarehouseCounter;
use Oro\Bundle\InventoryBundle\Form\Extension\OrderFormExtension;

class OrderFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseCounter;

    /**
     * @var OrderFormExtension
     */
    protected $orderFormExtension;

    protected function setUp()
    {
        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFormExtension = new OrderFormExtension($this->warehouseCounter);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->getMockBuilder(FormBuilderInterface::class)->getMock();
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);
        $builder->expects($this->once())
            ->method('add')
            ->willReturnCallback(function ($name) {
                $this->assertEquals('warehouse', $name);
            });

        $this->orderFormExtension->buildForm($builder, []);
    }

    public function testBuildFormDoesNotAddWarehouseField()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->getMockBuilder(FormBuilderInterface::class)->getMock();
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);
        $builder->expects($this->never())
            ->method('add');

        $this->orderFormExtension->buildForm($builder, []);
    }
}
