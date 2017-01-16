<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method;

use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

class MoneyOrderTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;

    /** @var MoneyOrder */
    protected $method;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $config = new MoneyOrderConfig($this->configManager);
        $this->method = new MoneyOrder($config);
    }

    public function testExecute()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());

        $this->assertEquals([], $this->method->execute('', $transaction));
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testGetType()
    {
        $this->assertEquals(MoneyOrder::TYPE, $this->method->getIdentifier());
    }

    /**
     * @param bool $expected
     * @param string $actionName
     *
     * @dataProvider supportsDataProvider
     */
    public function testSupports($expected, $actionName)
    {
        $this->assertEquals($expected, $this->method->supports($actionName));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [false, MoneyOrder::AUTHORIZE],
            [false, MoneyOrder::CAPTURE],
            [false, MoneyOrder::CHARGE],
            [false, MoneyOrder::VALIDATE],
            [true, MoneyOrder::PURCHASE],
        ];
    }

    public function testIsApplicable()
    {
        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertTrue($this->method->isApplicable($context));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroMoneyOrderExtension::ALIAS;
    }
}
