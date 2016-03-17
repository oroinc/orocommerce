<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\TaxBundle\Form\Extension\OrderLineItemTypeExtension;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

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
     * @var OrderLineItemTypeExtension
     */
    protected $extension;


    protected function setUp()
    {
        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxationSettingsProvider = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twig = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OrderLineItemTypeExtension(
            $this->taxationSettingsProvider,
            $this->taxManager
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
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'taxes',
                'hidden',
                [
                    'required' => false,
                    'mapped' => false,
                ]
            )
            ->willReturn($builder);

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

        $this->assertArrayHasKey('taxes', $view->children);
        $this->assertArrayHasKey('result', $view->children['taxes']->vars);
        $this->assertEquals($result, $view->children['taxes']->vars['result']);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefault('sections', []);
        $this->extension->configureOptions($resolver);
        $options = $resolver->resolve();

        $this->assertArrayHasKey('sections', $options);
        $this->assertArrayHasKey('taxes', $options['sections']);
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
