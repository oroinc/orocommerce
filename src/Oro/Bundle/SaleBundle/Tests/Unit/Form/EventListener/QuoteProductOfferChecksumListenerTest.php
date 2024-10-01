<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\EventListener\QuoteProductOfferChecksumListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class QuoteProductOfferChecksumListenerTest extends TestCase
{
    private LineItemChecksumGeneratorInterface|MockObject $lineItemChecksumGenerator;

    private QuoteProductOfferChecksumListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->listener = new QuoteProductOfferChecksumListener($this->lineItemChecksumGenerator);
    }

    public function testOnPostSubmitWhenNoData(): void
    {
        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onPostSubmit(new FormEvent($this->createMock(FormInterface::class), null));
    }

    public function testOnPostSubmitWhenNoQuoteProductOffers(): void
    {
        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onPostSubmit(new FormEvent($this->createMock(FormInterface::class), new QuoteProduct()));
    }

    public function testOnPostSubmitWhenChecksumIsNull(): void
    {
        $lineItem = (new QuoteProductOffer())
            ->setChecksum('sample_checksum');

        $requestProduct = new QuoteProduct();
        $requestProduct->addQuoteProductOffer($lineItem);

        $event = new FormEvent($this->createMock(FormInterface::class), $requestProduct);

        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        $this->listener->onPostSubmit($event);

        self::assertSame('', $lineItem->getChecksum());
    }

    public function testOnPostSubmitWhenChecksumNotNull(): void
    {
        $lineItem = (new QuoteProductOffer())
            ->setChecksum('sample_checksum');

        $requestProduct = new QuoteProduct();
        $requestProduct->addQuoteProductOffer($lineItem);

        $event = new FormEvent($this->createMock(FormInterface::class), $requestProduct);

        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn('updated_checksum');

        $this->listener->onPostSubmit($event);

        self::assertSame('updated_checksum', $lineItem->getChecksum());
    }
}
