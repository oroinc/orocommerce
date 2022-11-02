<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\EventListener;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodConfigRepository;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;
use Oro\Bundle\PaymentBundle\Method\EventListener\MethodRenamingListener;

class MethodRenamingListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentMethodConfigRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMethodConfigRepository;

    /**
     * @var PaymentTransactionRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentTransactionRepository;

    /**
     * @var MethodRenamingListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->paymentMethodConfigRepository = $this->createMock(PaymentMethodConfigRepository::class);
        $this->paymentTransactionRepository = $this->createMock(PaymentTransactionRepository::class);
        $this->listener = new MethodRenamingListener(
            $this->paymentMethodConfigRepository,
            $this->paymentTransactionRepository
        );
    }

    public function testOnMethodRename()
    {
        $oldId = 'old_name';
        $newId = 'new_name';

        /** @var MethodRenamingEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(MethodRenamingEvent::class);
        $event->expects(static::any())
            ->method('getOldMethodIdentifier')
            ->willReturn($oldId);

        $event->expects(static::any())
            ->method('getNewMethodIdentifier')
            ->willReturn($newId);

        $config1 = $this->createMock(PaymentMethodConfig::class);
        $config1->expects(static::once())
            ->method('setType')
            ->with($newId);
        $config2 = $this->createMock(PaymentMethodConfig::class);
        $config2->expects(static::once())
            ->method('setType')
            ->with($newId);

        $this->paymentMethodConfigRepository->expects(static::once())
            ->method('findByType')
            ->with($oldId)
            ->willReturn([$config1, $config2]);

        $transaction1 = $this->createMock(PaymentTransaction::class);
        $transaction1->expects(static::once())
            ->method('setPaymentMethod')
            ->with($newId);
        $transaction2 = $this->createMock(PaymentTransaction::class);
        $transaction2->expects(static::once())
            ->method('setPaymentMethod')
            ->with($newId);

        $this->paymentTransactionRepository->expects(static::once())
            ->method('findByPaymentMethod')
            ->with($oldId)
            ->willReturn([$transaction1, $transaction2]);

        $this->listener->onMethodRename($event);
    }
}
