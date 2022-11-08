<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Formatter;

use Oro\Bundle\OrderBundle\Formatter\ShippingTrackingFormatter;
use Oro\Bundle\OrderBundle\Tests\Unit\Formatter\Stub\ShippingMethodStub;
use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProviderInterface;

class ShippingTrackingFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TrackingAwareShippingMethodsProviderInterface */
    private $trackingAwareShippingMethodsProvider;

    protected function setUp(): void
    {
        $this->trackingAwareShippingMethodsProvider = $this->createMock(
            TrackingAwareShippingMethodsProviderInterface::class
        );
    }

    private function getShippingMethod(?string $trackingLink, ?string $label, bool $trackingAware): ShippingMethodStub
    {
        $method = $this->createMock(ShippingMethodStub::class);
        if ($trackingAware) {
            $method->expects(self::any())
                ->method('getTrackingLink')
                ->willReturn('http://tracking.com?number=' . $trackingLink);
            $method->expects(self::any())
                ->method('getLabel')
                ->willReturn($label);
        } else {
            $method->expects(self::any())
                ->method('getTrackingLink')
                ->willReturn(null);
            $method->expects(self::any())
                ->method('getLabel')
                ->willReturn(null);
        }
        return $method;
    }

    /**
     * @dataProvider shippingTrackingLinkProvider
     */
    public function testFormatShippingTrackingLink(
        string $shippingMethod,
        string $trackingNumber,
        bool $trackingAware,
        string $expectedResult
    ) {
        $formatter = new ShippingTrackingFormatter($this->trackingAwareShippingMethodsProvider);

        $this->trackingAwareShippingMethodsProvider->expects(self::any())
            ->method('getTrackingAwareShippingMethods')
            ->willReturn([
                $shippingMethod => $this->getShippingMethod($trackingNumber, null, $trackingAware)
            ]);

        self::assertEquals(
            $expectedResult,
            $formatter->formatShippingTrackingLink($shippingMethod, $trackingNumber)
        );
    }

    public function shippingTrackingLinkProvider(): array
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
     */
    public function testFormatShippingTrackingMethod(
        string $shippingMethod,
        ?string $label,
        bool $trackingAware,
        string $expectedResult
    ) {
        $formatter = new ShippingTrackingFormatter($this->trackingAwareShippingMethodsProvider);

        $this->trackingAwareShippingMethodsProvider->expects(self::any())
            ->method('getTrackingAwareShippingMethods')
            ->willReturn([
                $shippingMethod => $this->getShippingMethod(null, $label, $trackingAware)
            ]);

        self::assertEquals(
            $expectedResult,
            $formatter->formatShippingTrackingMethod($shippingMethod)
        );
    }

    public function shippingTrackingMethodProvider(): array
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
