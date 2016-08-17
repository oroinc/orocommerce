<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Twig;

use OroB2B\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

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
            ->with('orob2b.payment.status.full')
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
                ['orob2b.payment.status.' . PaymentStatusProvider::FULL],
                ['orob2b.payment.status.' . PaymentStatusProvider::AUTHORIZED],
                ['orob2b.payment.status.' . PaymentStatusProvider::PENDING],
                ['orob2b.payment.status.' . PaymentStatusProvider::DECLINED],
                ['orob2b.payment.status.' . PaymentStatusProvider::PARTIALLY]
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
