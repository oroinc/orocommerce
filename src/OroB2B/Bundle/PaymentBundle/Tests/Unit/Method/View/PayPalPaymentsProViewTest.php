<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Method\View\PayPalPaymentsProView;
use OroB2B\Bundle\PaymentBundle\Method\PayPalPaymentsPro;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class PayPalPaymentsProViewTest extends \PHPUnit_Framework_TestCase
{
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

    public function testGetOrder()
    {
        $order = 100;
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_SORT_ORDER_KEY, $order);

        $this->assertEquals($order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals(PayPalPaymentsPro::TYPE, $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }

    public function testGetAllowedCreditCards()
    {
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getAllowedCreditCards());
    }

    /**
     * @param mixed $expects
     * @param string $key
     * @param mixed $value
     */
    protected function setConfig($expects, $key, $value)
    {
        $this->configManager->expects($expects)
            ->method('get')
            ->with($this->getConfigKey($key))
            ->willReturn($value);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getConfigKey($key)
    {
        return OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }

}
