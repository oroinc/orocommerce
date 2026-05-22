<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Webhook;

use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\IntegrationBundle\Model\WebhookNotifierInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentInfoProviderInterface;
use Oro\Bundle\PaymentBundle\Webhook\PaymentStatusWebhookNotifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PaymentStatusWebhookNotifierTest extends TestCase
{
    private WebhookNotifierInterface&MockObject $webhookNotifier;
    private PaymentInfoProviderInterface&MockObject $paymentInfoProvider;
    private PaymentStatusLabelFormatter&MockObject $paymentStatusLabelFormatter;
    private EntityAliasResolverRegistry&MockObject $entityAliasResolverRegistry;
    private EntityAliasResolver&MockObject $entityAliasResolver;
    private PaymentStatusWebhookNotifier $notifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookNotifier = $this->createMock(WebhookNotifierInterface::class);
        $this->paymentInfoProvider = $this->createMock(PaymentInfoProviderInterface::class);
        $this->paymentStatusLabelFormatter = $this->createMock(PaymentStatusLabelFormatter::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityAliasResolverRegistry = $this->createMock(EntityAliasResolverRegistry::class);

        $this->notifier = new PaymentStatusWebhookNotifier(
            $this->webhookNotifier,
            $this->paymentInfoProvider,
            $this->paymentStatusLabelFormatter,
            $this->entityAliasResolverRegistry
        );
    }

    public function testNotifySendsCorrectPayload(): void
    {
        $topic = 'order.payment_status_updated';
        $entityClass = \stdClass::class;
        $entityApiType = 'orders';
        $entityId = 42;
        $paymentStatusCode = 'paid';
        $totalAmount = 150.00;
        $amountPaid = 100.00;
        $amountDue = 50.00;
        $statusLabel = 'Paid';

        $paymentTransaction = (new PaymentTransaction())
            ->setAmount('100.25')
            ->setAction('capture')
            ->setEntityClass($entityClass)
            ->setEntityIdentifier($entityId)
            ->setCurrency('USD')
            ->setCreatedAt(new \DateTime('2008-09-10 11:12:13', new \DateTimeZone('UTC')));

        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus($paymentStatusCode);

        $this->paymentInfoProvider->expects(self::once())
            ->method('getPaymentStatus')
            ->with($entityClass, $entityId)
            ->willReturn($paymentStatus);

        $this->entityAliasResolverRegistry->expects(self::any())
            ->method('getEntityAliasResolver')
            ->with(new RequestType([RequestType::REST, RequestType::JSON_API]))
            ->willReturn($this->entityAliasResolver);

        $this->entityAliasResolver->expects(self::once())
            ->method('getPluralAlias')
            ->with($entityClass)
            ->willReturn($entityApiType);

        $this->paymentInfoProvider->expects(self::once())
            ->method('getAmountPaid')
            ->with($entityClass, $entityId)
            ->willReturn($amountPaid);

        $this->paymentInfoProvider->expects(self::once())
            ->method('getAmountDue')
            ->with($entityClass, $entityId, $totalAmount)
            ->willReturn($amountDue);

        $this->paymentStatusLabelFormatter->expects(self::once())
            ->method('formatPaymentStatusLabel')
            ->with($paymentStatusCode)
            ->willReturn($statusLabel);

        $expectedEventData = [
            'data' => [
                'type' => $entityApiType,
                'id' => $entityId,
                'attributes' => [
                    'paymentStatus' => $paymentStatusCode,
                    'paymentStatusLabel' => $statusLabel,
                    'transactionAmount' => 100.25,
                    'transactionType' => 'capture',
                    'transactionDate' => '2008-09-10 11:12:13',
                    'amountPaid' => $amountPaid,
                    'amountDue' => $amountDue,
                    'currency' => 'USD',
                ],
            ],
        ];

        $this->webhookNotifier->expects(self::exactly(2))
            ->method('sendNotification')
            ->withConsecutive(
                [$topic, $expectedEventData],
                [$topic . '.' . $entityId, $expectedEventData]
            );

        $this->notifier->notify($topic, $paymentTransaction, $totalAmount);
    }
}
