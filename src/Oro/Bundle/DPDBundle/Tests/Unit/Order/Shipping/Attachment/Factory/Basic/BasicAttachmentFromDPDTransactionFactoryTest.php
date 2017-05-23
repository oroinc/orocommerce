<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Order\Shipping\Attachment\Factory\Basic;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Comment\Provider\OrderShippingAttachmentCommentProviderInterface;
use Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Factory\Basic\BasicAttachmentFromDPDTransactionFactory;
use Oro\Bundle\OrderBundle\Entity\Order;

class BasicAttachmentFromDPDTransactionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderShippingAttachmentCommentProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attachmentCommentProvider;

    /**
     * @var BasicAttachmentFromDPDTransactionFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->attachmentCommentProvider = $this->createMock(OrderShippingAttachmentCommentProviderInterface::class);
        $this->factory = new BasicAttachmentFromDPDTransactionFactory($this->attachmentCommentProvider);
    }

    public function testCreateAttachmentFromDPDTransaction()
    {
        $transaction = $this->createMock(DPDTransaction::class);

        $order = $this->createMock(Order::class);

        $transaction->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $file = $this->createMock(File::class);

        $transaction->expects(static::once())
            ->method('getLabelFile')
            ->willReturn($file);

        $comment = 'file comment';

        $this->attachmentCommentProvider->expects(static::once())
            ->method('getAttachmentComment')
            ->with($transaction)
            ->willReturn($comment);

        $attachment = new Attachment();
        $attachment->setTarget($order);
        $attachment->setFile($file);
        $attachment->setComment($comment);

        $actualAttachment = $this->factory->createAttachmentFromDPDTransaction($transaction);

        static::assertEquals($attachment, $actualAttachment);
        static::assertSame($file, $actualAttachment->getFile());
    }
}
