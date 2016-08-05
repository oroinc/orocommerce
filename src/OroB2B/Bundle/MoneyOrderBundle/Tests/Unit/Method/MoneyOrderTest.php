<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Tests\Unit\Method;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;
use OroB2B\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use OroB2B\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

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

    protected function tearDown()
    {
        unset($this->method, $this->configManager);
    }

    public function testExecute()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());

        $this->assertEquals([], $this->method->execute('', $transaction));
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testIsEnabled()
    {
        $this->setConfig($this->at(0), Configuration::MONEY_ORDER_ENABLED_KEY, true);
        $this->assertTrue($this->method->isEnabled());

        $this->setConfig($this->at(0), Configuration::MONEY_ORDER_ENABLED_KEY, false);
        $this->assertFalse($this->method->isEnabled());
    }

    public function testGetType()
    {
        $this->assertEquals(MoneyOrder::TYPE, $this->method->getType());
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
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->getConfigKey(Configuration::MONEY_ORDER_ALLOWED_COUNTRIES_KEY)],
                [$this->getConfigKey(Configuration::MONEY_ORDER_ALLOWED_CURRENCIES)]
            )
            ->willReturnOnConsecutiveCalls(PaymentConfiguration::ALLOWED_COUNTRIES_ALL, ['USD']);

        $this->assertTrue($this->method->isApplicable(['currency' => 'USD']));
    }

    public function testIsApplicableWithoutCountry()
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->getConfigKey(Configuration::MONEY_ORDER_ALLOWED_COUNTRIES_KEY)],
                [$this->getConfigKey(Configuration::MONEY_ORDER_SELECTED_COUNTRIES_KEY)]
            )
            ->willReturnOnConsecutiveCalls(PaymentConfiguration::ALLOWED_COUNTRIES_SELECTED, []);

        $this->assertFalse($this->method->isApplicable(['country' => 'US']));
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroB2BMoneyOrderExtension::ALIAS;
    }
}
