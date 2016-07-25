<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\OrderBundle\Form\Section\SectionProvider;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use OroB2B\Bundle\WarehouseBundle\Form\Extension\OrderLineItemFormExtension;

class OrderLineItemFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SectionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sectionProvider;

    /**
     * @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseCounter;

    /**
     * @var OrderLineItemFormExtension
     */
    protected $orderLineItemFormExtension;

    protected function setUp()
    {
        $this->sectionProvider = $this->getMockBuilder(SectionProvider::class)->getMock();
        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderLineItemFormExtension = new OrderLineItemFormExtension(
            $this->sectionProvider,
            $this->warehouseCounter
        );
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
        $this->orderLineItemFormExtension->buildForm($builder, []);
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

        $this->orderLineItemFormExtension->buildForm($builder, []);
    }

    public function testBuildView()
    {
        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view * */
        $view = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);

        $this->sectionProvider->expects($this->once())
            ->method('addSections')
            ->willReturnCallback(function ($name) {
                $this->assertEquals(OrderLineItemType::NAME, $name);
            });

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form * */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();

        $this->orderLineItemFormExtension->buildView($view, $form, []);
    }

    public function testBuildViewShouldNotAddSection()
    {
        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $view * */
        $view = $this->getMockBuilder(FormView::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);

        $this->sectionProvider->expects($this->never())
            ->method('addSections');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form * */
        $form = $this->getMockBuilder(FormInterface::class)->getMock();

        $this->orderLineItemFormExtension->buildView($view, $form, []);
    }
}
