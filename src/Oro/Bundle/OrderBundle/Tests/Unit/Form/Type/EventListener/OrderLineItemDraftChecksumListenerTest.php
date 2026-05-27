<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderLineItemDraftChecksumListener;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

final class OrderLineItemDraftChecksumListenerTest extends TestCase
{
    private LineItemChecksumGeneratorInterface&MockObject $lineItemChecksumGenerator;

    private OrderLineItemDraftChecksumListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->listener = new OrderLineItemDraftChecksumListener($this->lineItemChecksumGenerator);
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                FormEvents::POST_SUBMIT => ['onPostSubmit', 255],
            ],
            OrderLineItemDraftChecksumListener::getSubscribedEvents()
        );
    }

    public function testOnPostSubmitWhenDataIsNull(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);

        $this->lineItemChecksumGenerator
            ->expects(self::never())
            ->method('getChecksum');

        $this->listener->onPostSubmit($event);
    }

    public function testOnPostSubmitWhenChecksumGeneratorReturnsNull(): void
    {
        $lineItem = new OrderLineItem();
        $lineItem->setChecksum('existing_checksum');

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $lineItem);

        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with(self::identicalTo($lineItem))
            ->willReturn(null);

        $this->listener->onPostSubmit($event);

        self::assertSame('', $lineItem->getChecksum());
    }

    public function testOnPostSubmitUpdatesChecksum(): void
    {
        $lineItem = new OrderLineItem();
        $lineItem->setChecksum('old_checksum');

        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $lineItem);

        $newChecksum = 'new_checksum_value';

        $this->lineItemChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with(self::identicalTo($lineItem))
            ->willReturn($newChecksum);

        $this->listener->onPostSubmit($event);

        self::assertSame($newChecksum, $lineItem->getChecksum());
    }
}
