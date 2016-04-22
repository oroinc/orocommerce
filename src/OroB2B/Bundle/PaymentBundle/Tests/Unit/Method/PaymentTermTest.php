<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
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

    protected function tearDown()
    {
        unset($this->method, $this->configManager, $this->paymentTermProvider);
    }

    public function testExecute()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());

        $this->assertEquals([], $this->method->execute($transaction));
        $this->assertTrue($transaction->isSuccessful());
    }

    /**
     * @dataProvider isEnabledProvider
     * @param bool $paymentTermPresent
     * @param bool $configValue
     * @param bool $expected
     */
    public function testIsEnabled($paymentTermPresent, $configValue, $expected)
    {
        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn($paymentTermPresent ? new PaymentTerm() : null);

        $this->setConfig(
            $paymentTermPresent ? $this->once() : $this->never(),
            Configuration::PAYMENT_TERM_ENABLED_KEY,
            $configValue
        );

        $this->assertEquals($expected, $this->method->isEnabled());
    }

    /**
     * @return array
     */
    public function isEnabledProvider()
    {
        return [
            [
                'paymentTermPresent' => true,
                'configValue' => true,
                'expected' => true
            ],
            [
                'paymentTermPresent' => false,
                'configValue' => true,
                'expected' => false
            ],
            [
                'paymentTermPresent' => true,
                'configValue' => false,
                'expected' => false
            ],
            [
                'paymentTermPresent' => false,
                'configValue' => false,
                'expected' => false
            ],
        ];
    }

    public function testGetType()
    {
        $this->assertEquals('payment_term', $this->method->getType());
    }
}
