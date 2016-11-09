<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Formatter;

use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Tests\Unit\Formatter\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class ShippingTrackingFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShippingMethodRegistry
     */
    protected $shippingMethodRegistry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->shippingMethodRegistry = $this
            ->getMockBuilder(ShippingMethodRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
    }


    /**
     * @param string|null $trackingLink
     * @param string|null $label
     * @param bool $trackingAware
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function shippingMethodMock($trackingLink, $label, $trackingAware = true)
    {
        if ($trackingAware) {
            $method = $this->getMockBuilder(ShippingMethodStub::class)->getMock();
            $method->expects(static::any())
                ->method('getTrackingLink')
                ->willReturn('http://tracking.com?number=' . $trackingLink);
            $method->expects(static::any())
                ->method('getLabel')
                ->willReturn($label);
        } else {
            $method = $this->getMockBuilder(ShippingMethodStub::class)->getMock();
            $method->expects(static::any())
                ->method('getTrackingLink')
                ->willReturn(null);
            $method->expects(static::any())
                ->method('getLabel')
                ->willReturn(null);
        }
        return $method;
    }

    /**
     * @dataProvider shippingTrackingLinkProvider
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
        $formatter = new ShippingTrackingFormatter($this->shippingMethodRegistry);

        $this->shippingMethodRegistry
            ->expects(static::any())
            ->method('getTrackingAwareShippingMethods')
            ->willReturn([
                $shippingMethod => $this->shippingMethodMock($trackingNumber, null, $trackingAware)
            ]);

        static::assertEquals(
            $expectedResult,
            $formatter->formatShippingTrackingLink($shippingMethod, $trackingNumber)
        );
    }

    /**
     * @return array
     */
    public function shippingTrackingLinkProvider()
    {
        return [
            [
                'shippingMethod' => 'shipping_method_1',
                'trackingNumber' => '1z999999999',
                'trackingAware' => true,
                'expectedResult' => 'http://tracking.com?number=1z999999999',
            ],
            [
                'shippingMethod' => 'shipping_method_2',
                'trackingNumber' => '1z111111111',
                'trackingAware' => false,
                'expectedResult' => '1z111111111',
            ],
        ];
    }

    /**
     * @dataProvider shippingTrackingMethodProvider
     * @param string $shippingMethod
     * @param string|null $label
     * @param bool $trackingAware
     * @param string $expectedResult
     */
    public function testFormatShippingTrackingMethod(
        $shippingMethod,
        $label,
        $trackingAware,
        $expectedResult
    ) {
        $formatter = new ShippingTrackingFormatter($this->shippingMethodRegistry);

        $this->shippingMethodRegistry
            ->expects(static::any())
            ->method('getTrackingAwareShippingMethods')
            ->willReturn([
                $shippingMethod => $this->shippingMethodMock(null, $label, $trackingAware)
            ]);

        static::assertEquals(
            $expectedResult,
            $formatter->formatShippingTrackingMethod($shippingMethod)
        );
    }

    /**
     * @return array
     */
    public function shippingTrackingMethodProvider()
    {
        return [
            [
                'shippingMethod' => 'shipping_method_1',
                'label' => 'Method 1',
                'trackingAware' => true,
                'expectedResult' => 'Method 1',
            ],
            [
                'shippingMethod' => 'shipping_method_2',
                'label' => null,
                'trackingAware' => false,
                'expectedResult' => 'shipping_method_2',
            ],
        ];
    }
}
