<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use OroB2B\Bundle\PricingBundle\Layout\DataProvider\CurrencyDataProvider;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class CurrencyDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencyDataProvider
     */
    protected $dataProvider;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userCurrencyManager;

    protected function setUp()
    {
        $this->userCurrencyManager = $this->getMock(UserCurrencyManager::class, [], [], '', false);
        $this->dataProvider = new CurrencyDataProvider($this->userCurrencyManager);
    }

    public function testGetDefaultCurrency()
    {
        $this->userCurrencyManager
            ->expects($this->once())
            ->method('getDefaultCurrency')
            ->willReturn('USD');

        $this->assertEquals('USD', $this->dataProvider->getDefaultCurrency());
    }

    public function testGetAvailableCurrencies()
    {
        $this->userCurrencyManager
            ->expects($this->once())
            ->method('getAvailableCurrencies')
            ->willReturn(['EUR']);

        $this->assertEquals(['EUR'], $this->dataProvider->getAvailableCurrencies());
    }

    public function testGetUserCurrency()
    {
        $this->userCurrencyManager
            ->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('UAH');

        $this->assertEquals('UAH', $this->dataProvider->getUserCurrency());
    }
}
