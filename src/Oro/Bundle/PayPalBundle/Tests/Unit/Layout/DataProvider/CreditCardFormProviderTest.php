<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Oro\Bundle\PayPalBundle\Layout\DataProvider\CreditCardFormProvider;

class CreditCardFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFactoryInterface| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var CreditCardFormProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->provider = new CreditCardFormProvider($this->formFactory);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('oro_paypal_credit_card_form_provider', $this->provider->getIdentifier());
    }

    public function testGetData()
    {
        /**
         * @var ContextInterface| \PHPUnit_Framework_MockObject_MockObject $context
         */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, [], [])
            ->willReturn($form);

        $data = $this->provider->getData($context);
        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $data);
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $data->getForm());
    }
}
