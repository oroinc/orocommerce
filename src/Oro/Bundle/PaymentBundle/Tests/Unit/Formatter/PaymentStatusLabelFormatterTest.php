<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Formatter;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\AvailablePaymentStatusesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PaymentStatusLabelFormatterTest extends TestCase
{
    private PaymentStatusLabelFormatter $formatter;
    private AvailablePaymentStatusesProvider&MockObject $availablePaymentStatusesProvider;
    private TranslatorInterface&MockObject $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->availablePaymentStatusesProvider = $this->createMock(AvailablePaymentStatusesProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new PaymentStatusLabelFormatter(
            $this->availablePaymentStatusesProvider,
            $this->translator
        );
    }

    public function testFormatPaymentStatusLabel(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.payment.status.full')
            ->willReturn('Paid in Full');

        $result = $this->formatter->formatPaymentStatusLabel('full');
        self::assertEquals('Paid in Full', $result);
    }

    public function testFormatPaymentStatusLabelWithUntranslatedStatus(): void
    {
        $paymentStatus = 'custom_payment_status';
        $translationKey = 'oro.payment.status.custom_payment_status';

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with($translationKey)
            ->willReturn($translationKey); // Return key when not translated

        $result = $this->formatter->formatPaymentStatusLabel($paymentStatus);
        self::assertEquals('Custom payment status', $result);
    }

    public function testFormatPaymentStatusLabelWithUnderscoresAndDashes(): void
    {
        $paymentStatus = 'CUSTOM_PAYMENT-STATUS_TEST';
        $translationKey = 'oro.payment.status.CUSTOM_PAYMENT-STATUS_TEST';

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with($translationKey)
            ->willReturn($translationKey); // Return key when not translated

        $result = $this->formatter->formatPaymentStatusLabel($paymentStatus);
        self::assertEquals('Custom payment status test', $result);
    }

    public function testGetAvailableStatusesWithoutEntityClass(): void
    {
        $availableStatuses = [
            PaymentStatuses::PAID_IN_FULL,
            PaymentStatuses::PAID_PARTIALLY,
            PaymentStatuses::AUTHORIZED,
            PaymentStatuses::DECLINED,
            PaymentStatuses::PENDING,
            PaymentStatuses::CANCELED,
        ];

        $this->availablePaymentStatusesProvider
            ->expects(self::once())
            ->method('getAvailablePaymentStatuses')
            ->with(null)
            ->willReturn($availableStatuses);

        $this->translator
            ->expects(self::exactly(count($availableStatuses)))
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'oro.payment.status.full' => 'Paid in Full',
                    'oro.payment.status.partially' => 'Paid Partially',
                    'oro.payment.status.authorized' => 'Authorized',
                    'oro.payment.status.declined' => 'Declined',
                    'oro.payment.status.pending' => 'Pending',
                    'oro.payment.status.canceled' => 'Canceled',
                    default => $key,
                };
            });

        $result = $this->formatter->getAvailableStatuses();

        $expected = [
            'Paid in Full' => PaymentStatuses::PAID_IN_FULL,
            'Paid Partially' => PaymentStatuses::PAID_PARTIALLY,
            'Authorized' => PaymentStatuses::AUTHORIZED,
            'Declined' => PaymentStatuses::DECLINED,
            'Pending' => PaymentStatuses::PENDING,
            'Canceled' => PaymentStatuses::CANCELED,
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetAvailableStatusesWithEntityClass(): void
    {
        $entityClass = 'App\Entity\Order';
        $availableStatuses = [
            PaymentStatuses::PAID_IN_FULL,
            PaymentStatuses::DECLINED,
            'custom_status',
        ];

        $this->availablePaymentStatusesProvider
            ->expects(self::once())
            ->method('getAvailablePaymentStatuses')
            ->with($entityClass)
            ->willReturn($availableStatuses);

        $this->translator
            ->expects(self::exactly(count($availableStatuses)))
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'oro.payment.status.full' => 'Paid in Full',
                    'oro.payment.status.declined' => 'Declined',
                    'oro.payment.status.custom_status' => 'oro.payment.status.custom_status', // Not translated
                    default => $key,
                };
            });

        $result = $this->formatter->getAvailableStatuses($entityClass);

        $expected = [
            'Paid in Full' => PaymentStatuses::PAID_IN_FULL,
            'Declined' => PaymentStatuses::DECLINED,
            'Custom status' => 'custom_status', // Fallback formatting applied
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetAvailableStatusesWithEmptyResult(): void
    {
        $this->availablePaymentStatusesProvider
            ->expects(self::once())
            ->method('getAvailablePaymentStatuses')
            ->with(null)
            ->willReturn([]);

        $this->translator
            ->expects(self::never())
            ->method('trans');

        $result = $this->formatter->getAvailableStatuses();

        self::assertEquals([], $result);
    }
}
