<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderLineItemChecksumListener;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class OrderLineItemChecksumListenerTest extends TestCase
{
    private LineItemChecksumGeneratorInterface|MockObject $lineItemChecksumGenerator;

    private OrderLineItemChecksumListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->listener = new OrderLineItemChecksumListener($this->lineItemChecksumGenerator);
    }

    public function testOnPostSubmitWhenNoData(): void
    {
        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onPostSubmit(new FormEvent($this->createMock(FormInterface::class), null));
    }

    public function testOnPostSubmitWhenChecksumIsNull(): void
    {
        $lineItem = (new OrderLineItem())
            ->setChecksum('sample_checksum');
        $event = new FormEvent($this->createMock(FormInterface::class), $lineItem);

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
        $lineItem = (new OrderLineItem())
            ->setChecksum('sample_checksum');
        $event = new FormEvent($this->createMock(FormInterface::class), $lineItem);

        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn('updated_checksum');

        $this->listener->onPostSubmit($event);

        self::assertSame('updated_checksum', $lineItem->getChecksum());
    }
}
