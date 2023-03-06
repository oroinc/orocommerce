<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class MultiShippingMethodProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $roundingService;

    /** @var MultiShippingCostProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingCostProvider;

    /** @var MultiShippingMethodProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->shippingCostProvider = $this->createMock(MultiShippingCostProvider::class);

        $this->provider = new MultiShippingMethodProvider(
            $this->memoryCacheProvider,
            $this->translator,
            $this->roundingService,
            $this->shippingCostProvider
        );
    }

    public function testProvider(): void
    {
        $shippingMethodIdentifier = 'multi_shipping';
        $shippingMethodLabel = 'label';
        $shippingMethod = new MultiShippingMethod(
            $shippingMethodIdentifier,
            $shippingMethodLabel,
            'bundles/oroshipping/img/multi-shipping-logo.png',
            true,
            $this->roundingService,
            $this->shippingCostProvider
        );

        $cached = false;
        $this->memoryCacheProvider->expects(self::exactly(5))
            ->method('get')
            ->with('shipping_methods_channel_multi_shipping')
            ->willReturnCallback(function ($arguments, $callable) use ($shippingMethod, &$cached) {
                if ($cached) {
                    return [$shippingMethod->getIdentifier() => $shippingMethod];
                }

                $cached = true;

                return $callable($arguments);
            });

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.shipping.multi_shipping_method.label')
            ->willReturn($shippingMethodLabel);

        self::assertEquals(
            [$shippingMethodIdentifier => $shippingMethod],
            $this->provider->getShippingMethods()
        );
        self::assertTrue($this->provider->hasShippingMethod($shippingMethodIdentifier));
        self::assertEquals($shippingMethod, $this->provider->getShippingMethod($shippingMethodIdentifier));
        self::assertFalse($this->provider->hasShippingMethod('another'));
        self::assertNull($this->provider->getShippingMethod('another'));
    }
}
