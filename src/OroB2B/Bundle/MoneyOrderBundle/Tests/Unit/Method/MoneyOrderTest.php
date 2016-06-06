<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\MoneyOrderBundle\Method\MoneyOrder as MoneyOrderMethod;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;

class MoneyOrderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var MoneyOrderMethod */
    protected $method;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->method = new MoneyOrderMethod($this->configManager);
    }

    protected function tearDown()
    {
        unset($this->method, $this->configManager);
    }

    public function testExecute()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());

        $this->assertEquals([], $this->method->execute($transaction));
        $this->assertTrue($transaction->isSuccessful());
    }

    /**
     * @param array $inputData
     * @param mixed $expectedData
     *
     * @dataProvider getConfigValueProvider
     */
    public function testGetConfigValue(array $inputData, $expectedData)
    {
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(OroB2BMoneyOrderExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR .$inputData['key'])
            ->willReturn($inputData['result']);
        
        $this->assertSame($expectedData, $this->method->getConfigValue($inputData['key']));
    }

    /**
     * @return array
     */
    public function getConfigValueProvider()
    {
        return [
            'null result' => [
                'input' => [
                    'key' => 'key',
                    'result' => null,
                ],
                'expected' => null,
            ],
            'value1' => [
                'input' => [
                    'key' => 'key',
                    'result' => 'value1',
                ],
                'expected' => 'value1',
            ],
        ];
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
            [false, MoneyOrderMethod::AUTHORIZE],
            [false, MoneyOrderMethod::CAPTURE],
            [false, MoneyOrderMethod::CHARGE],
            [false, MoneyOrderMethod::VALIDATE],
            [true, MoneyOrderMethod::PURCHASE],
        ];
    }

    public function testIsApplicable()
    {
        $this->setConfig(
            $this->once(),
            Configuration::MONEY_ORDER_ALLOWED_COUNTRIES_KEY,
            PaymentConfiguration::ALLOWED_COUNTRIES_ALL
        );

        $this->assertTrue($this->method->isApplicable([]));
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
     * @expectedException \LogicException
     */
    public function testCompleteTransaction()
    {
        $this->method->completeTransaction(new PaymentTransaction(), []);
    }
}
