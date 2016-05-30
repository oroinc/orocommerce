<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Tests\Unit\Method\View;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use OroB2B\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class MoneyOrderViewTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var MoneyOrderView */
    protected $methodView;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->methodView = new MoneyOrderView($this->configManager);
    }

    protected function tearDown()
    {
        unset($this->methodView, $this->configManager);
    }

    public function testGetOptions()
    {
        $data = ['pay_to' => 'Pay To', 'send_to' => 'Send To'];

        $this->setConfig($this->at(0), Configuration::MONEY_ORDER_PAY_TO_KEY, $data['pay_to']);
        $this->setConfig($this->at(1), Configuration::MONEY_ORDER_SEND_TO_KEY, $data['send_to']);

        $this->assertEquals($data, $this->methodView->getOptions());
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_money_order_widget', $this->methodView->getBlock());
    }

    public function testGetOrder()
    {
        $order = '100';
        $this->setConfig($this->once(), Configuration::MONEY_ORDER_SORT_ORDER_KEY, $order);
        $this->assertEquals((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals(MoneyOrder::TYPE, $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::MONEY_ORDER_LABEL_KEY, 'testValue');
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
        return OroB2BMoneyOrderExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
