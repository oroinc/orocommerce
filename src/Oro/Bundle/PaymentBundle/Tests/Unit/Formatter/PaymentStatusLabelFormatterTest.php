<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Formatter;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentStatusLabelFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentStatusLabelFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new PaymentStatusLabelFormatter($this->translator);
    }

    public function testFormatPaymentStatusLabel()
    {
        $this->translator->expects($this->once())->method('trans')
            ->with('oro.payment.status.full')
            ->willReturn('Paid is Full');

        $result = $this->formatter->formatPaymentStatusLabel('full');
        $this->assertEquals('Paid is Full', $result);
    }

    public function testGetAvailableStatuses()
    {
        $expected = [
            'full' => PaymentStatusProvider::FULL,
            'authorized' => PaymentStatusProvider::AUTHORIZED,
            'pending' => PaymentStatusProvider::PENDING,
            'declined' => PaymentStatusProvider::DECLINED,
            'partial' => PaymentStatusProvider::PARTIALLY
        ];
        $this->translator->expects($this->exactly(5))
            ->method('trans')
            ->withConsecutive(
                ['oro.payment.status.' . PaymentStatusProvider::FULL],
                ['oro.payment.status.' . PaymentStatusProvider::AUTHORIZED],
                ['oro.payment.status.' . PaymentStatusProvider::PENDING],
                ['oro.payment.status.' . PaymentStatusProvider::DECLINED],
                ['oro.payment.status.' . PaymentStatusProvider::PARTIALLY]
            )
            ->willReturnOnConsecutiveCalls(
                'full',
                'authorized',
                'pending',
                'declined',
                'partial'
            );
        $result = $this->formatter->getAvailableStatuses();
        $this->assertEquals($expected, $result);
    }
}
