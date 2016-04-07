<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Provider;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;

class UserCurrencyProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected $session;

    protected function setUp()
    {
        $this->session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new UserCurrencyProvider($this->session);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->registry);
    }

    /**
     * @dataProvider getUserCurrencyDataProvider
     * @param $sessionCurrencyValue
     */
    public function testGetUserCurrency($sessionCurrencyValue)
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn($sessionCurrencyValue);

        if ($sessionCurrencyValue) {
            $this->assertEquals($sessionCurrencyValue, $this->provider->getUserCurrency());
        } else {
            $this->assertEquals(UserCurrencyProvider::DEFAULT_CURRENCY, $this->provider->getUserCurrency());
        }
    }

    /**
     * @return array
     */
    public function getUserCurrencyDataProvider()
    {
        return [
            'currency is stored in session' => [
                'sessionCurrencyValue' => 'USD',
            ],
            'currency is not stored in session' => [
                'sessionCurrencyValue' => null,
            ]
        ];
    }
}
