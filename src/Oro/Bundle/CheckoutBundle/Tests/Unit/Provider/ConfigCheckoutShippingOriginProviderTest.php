<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\ConfigCheckoutShippingOriginProvider;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\SystemShippingOriginProvider;

class ConfigCheckoutShippingOriginProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetShippingOrigin(): void
    {
        $shippingOrigin = $this->createMock(ShippingOrigin::class);

        $systemShippingOriginProvider = $this->createMock(SystemShippingOriginProvider::class);
        $systemShippingOriginProvider->expects(self::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        $shippingOriginProvider = new ConfigCheckoutShippingOriginProvider($systemShippingOriginProvider);
        self::assertSame(
            $shippingOrigin,
            $shippingOriginProvider->getShippingOrigin($this->createMock(Checkout::class))
        );
    }
}
