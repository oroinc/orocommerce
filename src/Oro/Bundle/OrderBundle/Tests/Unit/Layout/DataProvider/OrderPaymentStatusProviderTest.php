<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Layout\DataProvider\OrderPaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderPaymentStatusProviderTest extends TestCase
{
    private MockObject&PaymentStatusManager $paymentStatusManager;

    private OrderPaymentStatusProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);

        $this->provider = new OrderPaymentStatusProvider($this->paymentStatusManager);
    }

    public function testGetPaymentStatus(): void
    {
        $order = new Order();
        $status = PaymentStatuses::AUTHORIZED;

        $this->paymentStatusManager->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus($status));

        self::assertEquals($status, $this->provider->getPaymentStatus($order));
    }
}
