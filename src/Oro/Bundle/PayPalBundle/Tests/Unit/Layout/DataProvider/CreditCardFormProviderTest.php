<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;

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

    public function testGetCreditCardForm()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, [])
            ->willReturn($form);

        $creditCardForm = $this->provider->getCreditCardForm();
        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $creditCardForm);
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $creditCardForm->getForm());
    }
}
