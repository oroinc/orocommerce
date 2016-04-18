<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm as PaymentTermMethod;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    /** @var PaymentTermMethod */
    protected $method;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->method = new PaymentTermMethod($this->paymentTermProvider, $this->configManager);
    }

    public function testExecute()
    {
        $transaction = new PaymentTransaction();

        $this->assertEquals([], $this->method->execute($transaction));
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testIsEnabled()
    {
        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(true);

        $this->setConfig($this->once(), Configuration::PAYMENT_TERM_ENABLED_KEY, true);

        $this->assertTrue($this->method->isEnabled());
    }

    public function testGetType()
    {
        $this->assertEquals(PaymentTermMethod::TYPE, $this->method->getType());
    }
}
