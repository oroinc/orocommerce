<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\TrackingAwareShippingMethodsProvider;
use Oro\Bundle\ShippingBundle\Tests\Unit\Method\Stub\TrackingAwareShippingMethodStub;

class TrackingAwareShippingMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingMethodProvider;

    /**
     * @var TrackingAwareShippingMethodsProvider
     */
    private $trackingAwareShippingMethodsProvider;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);
        $this->trackingAwareShippingMethodsProvider =
            new TrackingAwareShippingMethodsProvider($this->shippingMethodProvider);
    }

    /**
     * @dataProvider getTrackingAwareShippingMethodsProvider
     *
     * @param array $methods
     * @param int $trackingAwareCount
     */
    public function testGetTrackingAwareShippingMethods(array $methods, $trackingAwareCount)
    {
        $this->shippingMethodProvider->expects(static::once())
            ->method('getShippingMethods')
            ->willReturn($methods);

        static::assertCount(
            $trackingAwareCount,
            $this->trackingAwareShippingMethodsProvider->getTrackingAwareShippingMethods()
        );
    }

    /**
     * @return array
     */
    public function getTrackingAwareShippingMethodsProvider()
    {
        return [
            [
                'methods' => [
                    $this->mockShippingMethod(ShippingMethodInterface::class, 'method1'),
                    $this->mockShippingMethod(ShippingMethodInterface::class, 'method2'),
                    $this->mockShippingMethod(TrackingAwareShippingMethodStub::class, 'method3')
                ],
                'trackingAwareCount' => 1,
            ],
            [
                'methods' => [
                    $this->mockShippingMethod(ShippingMethodInterface::class, 'method1'),
                    $this->mockShippingMethod(ShippingMethodInterface::class, 'method2'),
                    $this->mockShippingMethod(TrackingAwareShippingMethodStub::class, 'method3'),
                    $this->mockShippingMethod(TrackingAwareShippingMethodStub::class, 'method4')
                ],
                'trackingAwareCount' => 2,
            ],
            [
                'methods' => [
                    $this->mockShippingMethod(ShippingMethodInterface::class, 'method1'),
                    $this->mockShippingMethod(ShippingMethodInterface::class, 'method2'),
                    $this->mockShippingMethod(TrackingAwareShippingMethodStub::class, 'method3'),
                    $this->mockShippingMethod(TrackingAwareShippingMethodStub::class, 'method4'),
                    $this->mockShippingMethod(TrackingAwareShippingMethodStub::class, 'method5'),

                ],
                'trackingAwareCount' => 3,
            ]

        ];
    }

    /**
     * @param string $class
     * @param string $identifier
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockShippingMethod($class, $identifier)
    {
        $method = $this->createMock($class);
        $method->expects(static::any())
            ->method('getIdentifier')
            ->willReturn($identifier);

        return $method;
    }
}
