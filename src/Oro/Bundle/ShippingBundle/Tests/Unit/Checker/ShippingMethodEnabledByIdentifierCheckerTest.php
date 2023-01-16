<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Checker;

use Oro\Bundle\ShippingBundle\Checker\ShippingMethodEnabledByIdentifierChecker;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ShippingMethodEnabledByIdentifierCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingMethodProvider;

    /** @var ShippingMethodEnabledByIdentifierChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->shippingMethodProvider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->checker = new ShippingMethodEnabledByIdentifierChecker($this->shippingMethodProvider);
    }

    public function testIsEnabledForEnabledMethod(): void
    {
        $identifier = 'shipping_method_1';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn($shippingMethod);

        self::assertTrue($this->checker->isEnabled($identifier));
    }

    public function testIsEnabledForDisabledMethod(): void
    {
        $identifier = 'shipping_method_1';

        $shippingMethod = $this->createMock(ShippingMethodInterface::class);
        $shippingMethod->expects(self::once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn($shippingMethod);

        self::assertFalse($this->checker->isEnabled($identifier));
    }

    public function testIsEnabledForNotExistingMethod(): void
    {
        $identifier = 'shipping_method_1';

        $this->shippingMethodProvider->expects(self::once())
            ->method('getShippingMethod')
            ->with($identifier)
            ->willReturn(null);

        self::assertFalse($this->checker->isEnabled($identifier));
    }
}
