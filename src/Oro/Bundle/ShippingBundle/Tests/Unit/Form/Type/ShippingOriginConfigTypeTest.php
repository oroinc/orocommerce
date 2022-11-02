<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\Type\ShippingOriginConfigType;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingOriginType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class ShippingOriginConfigTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingOriginConfigType */
    private $formType;

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
        $mockFormView = $this->createMock(FormView::class);

        $mockFormInterface = $this->createMock(FormInterface::class);
        $mockFormInterface->expects($this->once())
            ->method('getParent')
            ->willReturn(null);

        $this->formType->finishView($mockFormView, $mockFormInterface, []);
    }

    public function testFinishViewWithoutParentScopeValue()
    {
        $mockFormView = $this->createMock(FormView::class);

        $mockParentForm = $this->createMock(FormInterface::class);
        $mockParentForm->expects($this->once())
            ->method('has')
            ->with('use_parent_scope_value')
            ->willReturn(false);

        $mockFormInterface = $this->createMock(FormInterface::class);
        $mockFormInterface->expects($this->once())
            ->method('getParent')
            ->willReturn($mockParentForm);

        $this->formType->finishView($mockFormView, $mockFormInterface, []);
    }

    public function testFinishViewParentScopeValue()
    {
        $childView = $this->createMock(FormView::class);

        $mockFormView = $this->createMock(FormView::class);
        $mockFormView->children = [$childView];

        $mockParentScopeValueForm = $this->createMock(FormInterface::class);
        $mockParentScopeValueForm->expects($this->once())
            ->method('getData')
            ->willReturn('data');

        $mockParentForm = $this->createMock(FormInterface::class);
        $mockParentForm->expects($this->once())
            ->method('has')
            ->with('use_parent_scope_value')
            ->willReturn(true);
        $mockParentForm->expects($this->once())
            ->method('get')
            ->with('use_parent_scope_value')
            ->willReturn($mockParentScopeValueForm);

        $mockFormInterface = $this->createMock(FormInterface::class);
        $mockFormInterface->expects($this->once())
            ->method('getParent')
            ->willReturn($mockParentForm);

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
