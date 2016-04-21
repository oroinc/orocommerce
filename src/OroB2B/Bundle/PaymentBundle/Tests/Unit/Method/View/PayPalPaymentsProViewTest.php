<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\Method\View\PayPalPaymentsProView;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

class PayPalPaymentsProViewTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var PayPalPaymentsProView */
    protected $methodView;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->methodView = new PayPalPaymentsProView($this->formFactory, $this->configManager);
    }

    protected function tearDown()
    {
        unset($this->methodView, $this->configManager, $this->formFactory);
    }


    public function testGetOptions()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $allowedCards = ['visa', 'mastercard'];
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY, $allowedCards);

        $this->assertEquals(
            [
                'formView' => $formView,
                'allowedCreditCards' => $allowedCards,
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetOrder()
    {
        $order = '100';
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_SORT_ORDER_KEY, $order);

        $this->assertSame((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('paypal_payments_pro', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }

    public function testGetAllowedCreditCards()
    {
        $allowedCards = ['visa', 'mastercard'];
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY, $allowedCards);
        $this->assertEquals($allowedCards, $this->methodView->getAllowedCreditCards());
    }
}
