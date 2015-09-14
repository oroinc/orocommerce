<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Model\Stub\PriceListRequestHandlerStub as PriceListRequestHandler;

class AbstractPriceListRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListRequestHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new PriceListRequestHandler();
    }

    protected function tearDown()
    {
        unset($this->handler);
    }

    public function testGetShowTierPricesWithoutRequest()
    {
        $this->assertFalse($this->handler->getShowTierPrices());
    }

    /**
     * @dataProvider getShowTierPricesWithoutParamDataProvider
     *
     * @param mixed $value
     * @param bool $expected
     */
    public function testGetShowTierPricesWithoutParam($value, $expected)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->once())
            ->method('get')
            ->with(PriceListRequestHandler::TIER_PRICES_KEY)
            ->willReturn($value);

        $this->assertEquals($expected, $this->handler->getShowTierPrices());
    }

    /**
     * @return array
     */
    public function getShowTierPricesWithoutParamDataProvider()
    {
        return [
            [
                'value' => null,
                'expected' => false
            ],
            [
                'value' => false,
                'expected' => false
            ],
            [
                'value' => true,
                'expected' => true
            ],
            [
                'value' => 'true',
                'expected' => true
            ],
            [
                'value' => 'false',
                'expected' => false
            ],
            [
                'value' => 1,
                'expected' => true
            ],
            [
                'value' => 0,
                'expected' => false
            ],
            [
                'value' => -1,
                'expected' => false
            ],
            [
                'value' => '1',
                'expected' => true
            ],
            [
                'value' => '0',
                'expected' => false
            ],
            [
                'value' => '-1',
                'expected' => false
            ]
        ];
    }

    public function testGetShowTierPrices()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Request $request */
        $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $this->handler->setRequest($request);

        $request->expects($this->once())->method('get')->with(PriceListRequestHandler::TIER_PRICES_KEY)
            ->willReturn(true);

        $this->assertTrue($this->handler->getShowTierPrices());
    }
}
