<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\PricingBundle\Layout\DataProvider\FrontendAccountUserCurrencyProvider;

use Oro\Component\Layout\ContextInterface;

class FrontendAccountUserCurrencyProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userCurrencyProvider;

    /**
     * @var FrontendAccountUserCurrencyProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->userCurrencyProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataProvider = new FrontendAccountUserCurrencyProvider($this->userCurrencyProvider);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_account_frontend_account_user_currency', $this->dataProvider->getIdentifier());
    }

    public function testGetData()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->userCurrencyProvider->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('UAH');
        $this->assertEquals('UAH', $this->dataProvider->getData($context));
    }
}
