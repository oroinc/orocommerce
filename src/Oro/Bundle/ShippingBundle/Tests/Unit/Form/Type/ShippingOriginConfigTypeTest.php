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
        $formView = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn(null);

        $this->formType->finishView($formView, $form, []);
    }

    public function testFinishViewWithoutParentScopeValue()
    {
        $formView = $this->createMock(FormView::class);

        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->expects($this->once())
            ->method('has')
            ->with('use_parent_scope_value')
            ->willReturn(false);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);

        $this->formType->finishView($formView, $form, []);
    }

    public function testFinishViewParentScopeValue()
    {
        $childView = $this->createMock(FormView::class);

        $formView = $this->createMock(FormView::class);
        $formView->children = [$childView];

        $parentScopeValueForm = $this->createMock(FormInterface::class);
        $parentScopeValueForm->expects($this->once())
            ->method('getData')
            ->willReturn('data');

        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->expects($this->once())
            ->method('has')
            ->with('use_parent_scope_value')
            ->willReturn(true);
        $parentForm->expects($this->once())
            ->method('get')
            ->with('use_parent_scope_value')
            ->willReturn($parentScopeValueForm);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);

        $this->formType->finishView($formView, $form, []);

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
