<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PricingBundle\Layout\DataProvider\EnabledCurrenciesProvider;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class EnabledCurrenciesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userCurrencyManager;

    /**
     * @var EnabledCurrenciesProvider
     */
    protected $enabledCurrenciesProvider;

    protected function setUp()
    {
        $this->userCurrencyManager = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->enabledCurrenciesProvider = new EnabledCurrenciesProvider($this->userCurrencyManager);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_pricing_enabled_currencies', $this->enabledCurrenciesProvider->getIdentifier());
    }

    public function testGetData()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->userCurrencyManager->expects($this->once())
            ->method('getAvailableCurrencies')
            ->willReturn(['EUR']);
        $this->assertEquals(['EUR'], $this->enabledCurrenciesProvider->getData($context));
    }
}
