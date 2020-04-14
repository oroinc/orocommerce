<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\Type\ShippingOriginConfigType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingOriginType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ShippingOriginConfigTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingOriginConfigType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new ShippingOriginConfigType();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ShippingOriginConfigType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(ShippingOriginType::class, $this->formType->getParent());
    }

    public function testFinishViewWithoutParentForm()
    {
        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $mockFormView */
        $mockFormView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mockFormInterface */
        $mockFormInterface = $this->createMock('Symfony\Component\Form\FormInterface');
        $mockFormInterface->expects($this->once())->method('getParent')->willReturn(null);

        $this->formType->finishView($mockFormView, $mockFormInterface, []);
    }

    public function testFinishViewWithoutParentScopeValue()
    {
        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $mockFormView */
        $mockFormView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        $mockParentForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $mockParentForm->expects($this->once())->method('has')->with('use_parent_scope_value')->willReturn(false);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mockFormInterface */
        $mockFormInterface = $this->createMock('Symfony\Component\Form\FormInterface');
        $mockFormInterface->expects($this->once())->method('getParent')->willReturn($mockParentForm);

        $this->formType->finishView($mockFormView, $mockFormInterface, []);
    }

    public function testFinishViewParentScopeValue()
    {
        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $childView */
        $childView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $mockFormView */
        $mockFormView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();
        $mockFormView->children = [$childView];

        $mockParentScopeValueForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $mockParentScopeValueForm->expects($this->once())->method('getData')->willReturn('data');

        $mockParentForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $mockParentForm->expects($this->once())->method('has')->with('use_parent_scope_value')->willReturn(true);
        $mockParentForm->expects($this->once())
            ->method('get')
            ->with('use_parent_scope_value')
            ->willReturn($mockParentScopeValueForm);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mockFormInterface */
        $mockFormInterface = $this->createMock('Symfony\Component\Form\FormInterface');
        $mockFormInterface->expects($this->once())->method('getParent')->willReturn($mockParentForm);

        $this->formType->finishView($mockFormView, $mockFormInterface, []);

        $this->assertEquals(
            [
                'value' => null,
                'attr' => [],
                'use_parent_scope_value' => 'data'
            ],
            $childView->vars
        );
    }
}
