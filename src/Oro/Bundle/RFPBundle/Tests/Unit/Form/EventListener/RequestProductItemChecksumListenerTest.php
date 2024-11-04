<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Form\EventListener\RequestProductItemChecksumListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class RequestProductItemChecksumListenerTest extends TestCase
{
    private LineItemChecksumGeneratorInterface|MockObject $lineItemChecksumGenerator;

    private RequestProductItemChecksumListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->listener = new RequestProductItemChecksumListener($this->lineItemChecksumGenerator);
    }

    public function testOnPostSubmitWhenNoData(): void
    {
        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onPostSubmit(new FormEvent($this->createMock(FormInterface::class), null));
    }

    public function testOnPostSubmitWhenNoRequestProductItems(): void
    {
        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onPostSubmit(new FormEvent($this->createMock(FormInterface::class), new RequestProduct()));
    }

    public function testOnPostSubmitWhenChecksumIsNull(): void
    {
        $lineItem = (new RequestProductItem())
            ->setChecksum('sample_checksum');

        $requestProduct = new RequestProduct();
        $requestProduct->addRequestProductItem($lineItem);

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
        $lineItem = (new RequestProductItem())
            ->setChecksum('sample_checksum');

        $requestProduct = new RequestProduct();
        $requestProduct->addRequestProductItem($lineItem);

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
