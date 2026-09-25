<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\SessionTransferCheckoutUrlProvider;
use Oro\Bundle\OAuth2ServerBundle\Provider\SessionTransferReturnUrlProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SessionTransferCheckoutUrlProviderTest extends TestCase
{
    private const string RETURN_URL = 'https://storefront.example.com';
    private const int ORDER_ID = 42;

    private SessionTransferReturnUrlProvider&MockObject $sessionTransferReturnUrlProvider;

    private SessionTransferCheckoutUrlProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->sessionTransferReturnUrlProvider = $this->createMock(SessionTransferReturnUrlProvider::class);
        $this->provider = new SessionTransferCheckoutUrlProvider($this->sessionTransferReturnUrlProvider);
    }

    public function testGetReturnUrl(): void
    {
        $this->sessionTransferReturnUrlProvider
            ->expects(self::once())
            ->method('getReturnUrl')
            ->willReturn(self::RETURN_URL);

        self::assertSame(self::RETURN_URL, $this->provider->getReturnUrl());
    }

    public function testGetOrderViewUrl(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, self::ORDER_ID);

        $this->sessionTransferReturnUrlProvider
            ->expects(self::once())
            ->method('getReturnUrl')
            ->willReturn(self::RETURN_URL);

        self::assertSame(
            'https://storefront.example.com/customer/order/view/42',
            $this->provider->getOrderViewUrl($order)
        );
    }
}
