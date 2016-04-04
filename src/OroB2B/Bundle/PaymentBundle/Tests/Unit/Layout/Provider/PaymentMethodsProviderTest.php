<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Layout\Provider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermMethodType;
use OroB2B\Bundle\PaymentBundle\Layout\DataProvider\PaymentMethodsProvider;
use OroB2B\Bundle\PaymentBundle\Form\PaymentMethodTypeRegistry;

class PaymentMethodsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFactoryInterface| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var PaymentMethodTypeRegistry| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PaymentMethodsProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->registry = $this->getMock('OroB2B\Bundle\PaymentBundle\Form\PaymentMethodTypeRegistry');
        $this->provider = new PaymentMethodsProvider($this->formFactory, $this->registry);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals(PaymentMethodsProvider::NAME, $this->provider->getIdentifier());
    }

    public function testGetDataEmpty()
    {
        /**
         * @var ContextInterface| \PHPUnit_Framework_MockObject_MockObject $context
         */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $type = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermMethodType')
            ->disableOriginalConstructor()
            ->getMock();
        $type->expects($this->once())
        ->method('isMethodEnabled')
        ->willReturn(false);

        $this->registry->expects($this->once())
            ->method('getPaymentMethodTypes')
            ->willReturn([$type]);

        $this->formFactory->expects($this->never())
            ->method('create');

        $data = $this->provider->getData($context);
        $this->assertEmpty($data);
    }

    public function testGetData()
    {
        /**
         * @var ContextInterface| \PHPUnit_Framework_MockObject_MockObject $context
         */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $formView = new FormView();

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $type = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermMethodType')
            ->disableOriginalConstructor()
            ->getMock();
        $type->expects($this->once())
            ->method('isMethodEnabled')
            ->willReturn(true);
        $type->expects($this->once())
            ->method('getName')
            ->willReturn(PaymentTermMethodType::NAME);

        $this->registry->expects($this->once())
            ->method('getPaymentMethodTypes')
            ->willReturn([PaymentTermMethodType::NAME => $type]);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(PaymentTermMethodType::NAME, [], [])
            ->willReturn($form);

        $data = $this->provider->getData($context);
        $this->assertEquals([PaymentTermMethodType::NAME => $formView], $data);
    }
}
