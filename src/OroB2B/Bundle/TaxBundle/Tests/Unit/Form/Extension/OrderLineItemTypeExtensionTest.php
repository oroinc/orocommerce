<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
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
     * @var TaxationSettingsProvider
     */
    protected $taxationSettingsProvider;

    /**
     * @var TaxManager
     */
    protected $taxManager;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

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
            $this->taxManager,
            $this->twig
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
                null,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'orob2b.tax.result.label'
                ]
            )
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->extension, 'onPostSetData']);

        $this->extension->buildForm($builder, []);
    }

    public function testOnPostSetDataDisabledProvider()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $event = $this->createEvent('Data');

        $this->taxManager->expects($this->never())
            ->method('getTax');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataEmptyForm()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $event = $this->createEvent();

        $this->taxManager->expects($this->never())
            ->method('getTax');


        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataEmptyTaxes()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $event = $this->createEvent('Data');

        $result = new \ArrayObject();

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->willReturn($result);

        $this->twig->expects($this->never())
            ->method('render');

        $this->extension->onPostSetData($event);
    }

    public function testOnPostSetDataAllOk()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $event = $this->createEvent('Data');

        $result = new \ArrayObject();
        $result->offsetSet('Key', 'Result');

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->willReturn($result);

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('Text');

        $taxesForm = $event->getForm()->get('taxes');
        $taxesForm->expects($this->once())
            ->method('setData')
            ->with('Text');

        $this->extension->onPostSetData($event);
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

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data = null)
    {
        $taxesForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('taxes')
            ->willReturn($taxesForm);
        $mainForm->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        return new FormEvent($mainForm, $data);
    }

    public function testOnBuildFormWithDisabledTaxes()
    {
        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->never())
            ->method('add');

        $builder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }
}
