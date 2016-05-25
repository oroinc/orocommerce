<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PricingBundle\Layout\DataProvider\DefaultCurrencyProvider;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;

class DefaultCurrencyProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userCurrencyProvider;

    /**
     * @var DefaultCurrencyProvider
     */
    protected $defaultCurrencyProvider;

    protected function setUp()
    {
        $this->userCurrencyProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->defaultCurrencyProvider = new DefaultCurrencyProvider($this->userCurrencyProvider);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_pricing_default_currency', $this->defaultCurrencyProvider->getIdentifier());
    }

    public function testGetData()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->userCurrencyProvider->expects($this->once())
            ->method('getDefaultCurrency')
            ->willReturn('USD');
        $this->assertEquals('USD', $this->defaultCurrencyProvider->getData($context));
    }
}
