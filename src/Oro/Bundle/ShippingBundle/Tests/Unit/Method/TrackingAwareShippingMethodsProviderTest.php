<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Method\Stub\TrackingAwareShippingMethodStub;

class TrackingAwareShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var TrackingAwareShippingMethodsProvider */
    private $trackingAwareShippingMethodsProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->trackingAwareShippingMethodsProvider = new TrackingAwareShippingMethodsProvider(
            $this->shippingMethodProvider
        );
    }

    private function getShippingMethod(string $class, string $identifier): object
    {
        $method = $this->createMock($class);
        $method->expects(self::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $method;
    }

    /**
     * @dataProvider getTrackingAwareShippingMethodsProvider
     */
    public function testGetTrackingAwareShippingMethods(array $methods, int $trackingAwareCount)
    {
        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethods')
            ->willReturn($methods);

        self::assertCount(
            $trackingAwareCount,
            $this->trackingAwareShippingMethodsProvider->getTrackingAwareShippingMethods()
        );
    }

    public function getTrackingAwareShippingMethodsProvider(): array
    {
        return [
            [
                'methods' => [
                    $this->getShippingMethod(ShippingMethodInterface::class, 'method1'),
                    $this->getShippingMethod(ShippingMethodInterface::class, 'method2'),
                    $this->getShippingMethod(TrackingAwareShippingMethodStub::class, 'method3')
                ],
                'trackingAwareCount' => 1,
            ],
            [
                'methods' => [
                    $this->getShippingMethod(ShippingMethodInterface::class, 'method1'),
                    $this->getShippingMethod(ShippingMethodInterface::class, 'method2'),
                    $this->getShippingMethod(TrackingAwareShippingMethodStub::class, 'method3'),
                    $this->getShippingMethod(TrackingAwareShippingMethodStub::class, 'method4')
                ],
                'trackingAwareCount' => 2,
            ],
            [
                'methods' => [
                    $this->getShippingMethod(ShippingMethodInterface::class, 'method1'),
                    $this->getShippingMethod(ShippingMethodInterface::class, 'method2'),
                    $this->getShippingMethod(TrackingAwareShippingMethodStub::class, 'method3'),
                    $this->getShippingMethod(TrackingAwareShippingMethodStub::class, 'method4'),
                    $this->getShippingMethod(TrackingAwareShippingMethodStub::class, 'method5'),

                ],
                'trackingAwareCount' => 3,
            ]

        ];
    }
}
