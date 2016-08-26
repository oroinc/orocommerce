<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

use Symfony\Component\Translation\TranslatorInterface;

class PaymentStatusLabelFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentStatusLabelFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    public function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);

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
            PaymentStatusProvider::FULL => 'full',
            PaymentStatusProvider::AUTHORIZED => 'authorized',
            PaymentStatusProvider::PENDING => 'pending',
            PaymentStatusProvider::DECLINED => 'declined',
            PaymentStatusProvider::PARTIALLY => 'partial'
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
