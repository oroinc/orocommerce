<?php

namespace Oro\src\Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodTrackingLinkFormatter;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;

class ShippingMethodTrackingLinkFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodRegistry;

    /**
     * @var ShippingMethodTrackingLinkFormatter
     */
    protected $formatter;

    public function setUp()
    {
        $this->shippingMethodRegistry = $this
            ->getMockBuilder('Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = new ShippingMethodTrackingLinkFormatter(
            $this->shippingMethodRegistry
        );
    }

    /**
     * @param string $shippingMethod
     * @param string $trackingLink
     * @param bool $trackingAware
     */
    protected function shippingMethodMock($shippingMethod, $trackingLink, $trackingAware = true)
    {
        if ($trackingAware) {
            $method = $this->getMockBuilder(ShippingTrackingAwareInterface::class)->getMock();
            $method->expects(static::any())
                ->method('getTrackingLink')
                ->willReturn('http://tracking.com?number=' . $trackingLink);
        } else {
            $method = $this->getMockBuilder(ShippingMethodInterface::class)->getMock();
            $method->expects(static::any())
                ->method('getTrackingLink')
                ->willReturn($trackingLink);
        }
        $this->shippingMethodRegistry
            ->expects(static::any())
            ->method('getShippingMethod')
            ->with($shippingMethod)
            ->willReturn($method);
    }

    /**
     * @dataProvider shippingMethodTypeProvider
     * @param string $shippingMethod
     * @param string $trackingNumber
     * @param bool $trackingAware
     * @param string $expectedResult
     */
    public function testFormatShippingTrackingLink(
        $shippingMethod,
        $trackingNumber,
        $trackingAware,
        $expectedResult
    ) {
        $this->shippingMethodMock($shippingMethod, $trackingNumber, $trackingAware);

        static::assertEquals(
            $expectedResult,
            $this->formatter->formatShippingTrackingLink($shippingMethod, $trackingNumber)
        );
    }

    /**
     * @return array
     */
    public function shippingMethodTypeProvider()
    {
        return [
            [
                'shippingMethod'          => 'shipping_method_1',
                'trackingNumber'          => '1z999999999',
                'trackingAware'           => true,
                'expectedResult'          => "<a href='http://tracking.com?number=1z999999999'>1z999999999",
            ],
            [
                'shippingMethod'          => 'shipping_method_2',
                'trackingNumber'          => '1z111111111',
                'trackingAware'           => false,
                'expectedResult'          => '1z111111111',
            ],
        ];
    }
}
