<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Form\Section\SectionProvider;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\TaxBundle\Form\Extension\OrderLineItemTypeExtension;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider;

class OrderLineItemTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxationSettingsProvider;

    /**
     * @var TaxManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxManager;

    /**
     * @var TaxSubtotalProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $taxSubtotalProvider;

    /**
     * @var OrderLineItemTypeExtension
     */
    protected $extension;

    /** @var SectionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $sectionProvider;

    protected function setUp()
    {
        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxationSettingsProvider = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxSubtotalProvider = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sectionProvider = $this->getMock('OroB2B\Bundle\OrderBundle\Form\Section\SectionProvider');

        $this->extension = new OrderLineItemTypeExtension(
            $this->taxationSettingsProvider,
            $this->taxManager,
            $this->taxSubtotalProvider,
            $this->sectionProvider,
            OrderLineItemType::NAME
        );
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(OrderLineItemType::NAME, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->never())->method($this->anything());

        $this->taxSubtotalProvider->expects($this->once())->method('setEditMode')->with(true);

        $this->extension->buildForm($builder, []);
    }

    public function testFinishViewDisabledProvider()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->taxManager->expects($this->never())->method('getTax');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $view = new FormView();
        $this->extension->finishView($view, $form, []);
    }

    public function testFinishViewEmptyForm()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->taxManager->expects($this->never())->method('getTax');


        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getData')->willReturn(null);
        $view = new FormView();
        $this->extension->finishView($view, $form, []);
    }

    public function testFinishView()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $result = new \ArrayObject();
        $result->offsetSet('Key', 'Result');

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->willReturn($result);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getData')->willReturn(new \stdClass());
        $view = new FormView();
        $this->extension->finishView($view, $form, []);

        $this->assertArrayHasKey('result', $view->vars);
        $this->assertEquals($result, $view->vars['result']);
    }

    public function testBuildView()
    {
        $this->sectionProvider->expects($this->once())->method('addSections')
            ->with(
                $this->logicalAnd(
                    $this->isType('string'),
                    $this->equalTo($this->extension->getExtendedType())
                ),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('taxes')
                )
            );

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->extension->buildView($view, $form, []);
    }

    public function testBuildViewTaxDisabled()
    {
        $this->sectionProvider->expects($this->never())->method($this->anything());

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(false);

        $view = new FormView();
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->extension->buildView($view, $form, []);
    }

    public function testOnBuildFormWithDisabledTaxes()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->never())->method('add');

        $this->extension->buildForm($builder, []);
    }
}
