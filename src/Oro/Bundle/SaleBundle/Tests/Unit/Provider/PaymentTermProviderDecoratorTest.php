<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Provider\PaymentTermProviderDecorator;

class PaymentTermProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $innerProvider;

    /**
     * @var PaymentTermProviderDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PaymentTermProvider::class);
        $this->decorator = new PaymentTermProviderDecorator($this->innerProvider);
    }

    public function testGetObjectPaymentTermNotQuoteDemand()
    {
        $paymentTerm = new PaymentTerm();

        $object = $this->createMock(\stdClass::class);
        $this->innerProvider->expects($this->once())
            ->method('getObjectPaymentTerm')
            ->with($object)
            ->willReturn($paymentTerm);

        self::assertSame(
            $paymentTerm,
            $this->decorator->getObjectPaymentTerm($object)
        );
    }

    public function testGetObjectPaymentTerm()
    {
        $paymentTerm = new PaymentTerm();

        $quote = new Quote();
        $object = $this->createMock(QuoteDemand::class);
        $object->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);
        $this->innerProvider->expects($this->once())
            ->method('getObjectPaymentTerm')
            ->with($quote)
            ->willReturn($paymentTerm);

        self::assertSame(
            $paymentTerm,
            $this->decorator->getObjectPaymentTerm($object)
        );
    }
}
