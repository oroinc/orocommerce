<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Method\View\PayflowGatewayView;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class PayflowGatewayViewTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var PayflowGatewayView */
    protected $methodView;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->methodView = new PayflowGatewayView($this->formFactory, $this->configManager);
    }

    public function testGetOptions()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->any())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $this->setConfig($this->once(), Configuration::PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY, 'testValue');

        $this->assertEquals(
            [
                'formView' => $formView,
                'allowedCreditCards' => 'testValue'
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_credit_card_widget', $this->methodView->getBlock());
    }

    public function testGetOrder()
    {
        $order = 100;
        $this->setConfig($this->once(), Configuration::PAYFLOW_GATEWAY_SORT_ORDER_KEY, $order);

        $this->assertEquals($order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals(PayflowGateway::TYPE, $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYFLOW_GATEWAY_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
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
