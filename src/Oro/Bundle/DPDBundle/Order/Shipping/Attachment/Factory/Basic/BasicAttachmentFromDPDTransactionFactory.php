<?php

namespace Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Factory\Basic;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Comment\Provider\OrderShippingAttachmentCommentProviderInterface;
use Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Factory\AttachmentFromDPDTransactionFactoryInterface;

class BasicAttachmentFromDPDTransactionFactory implements AttachmentFromDPDTransactionFactoryInterface
{
    /**
     * @var OrderShippingAttachmentCommentProviderInterface
     */
    private $attachmentCommentProvider;

    /**
     * @param OrderShippingAttachmentCommentProviderInterface $attachmentCommentProvider
     */
    public function __construct(OrderShippingAttachmentCommentProviderInterface $attachmentCommentProvider)
    {
        $this->attachmentCommentProvider = $attachmentCommentProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createAttachmentFromDPDTransaction(DPDTransaction $transaction)
    {
        $attachment = new Attachment();
        $attachment->setTarget($transaction->getOrder());
        $attachment->setFile($transaction->getLabelFile());
        $attachment->setComment($this->getAttachmentComment($transaction));

        return $attachment;
    }

    /**
     * @param DPDTransaction $transaction
     *
     * @return string
     */
    private function getAttachmentComment(DPDTransaction $transaction)
    {
        return $this->attachmentCommentProvider->getAttachmentComment($transaction);
    }
}
