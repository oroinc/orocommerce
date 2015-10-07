<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Model\Stub\PriceListRequestHandlerStub as PriceListRequestHandler;

class AbstractPriceListRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceListRequestHandler
     */
    protected $handler;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;


    protected function setUp()
    {
        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->handler = new PriceListRequestHandler($requestStack);
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
        $this->request->expects($this->once())
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
        $this->request->expects($this->once())->method('get')->with(PriceListRequestHandler::TIER_PRICES_KEY)
            ->willReturn(true);

        $this->assertTrue($this->handler->getShowTierPrices());
    }
}
