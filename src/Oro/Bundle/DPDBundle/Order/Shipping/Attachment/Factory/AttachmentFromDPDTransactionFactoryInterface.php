<?php

namespace Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Factory;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\DPDBundle\Entity\DPDTransaction;

interface AttachmentFromDPDTransactionFactoryInterface
{
    /**
     * @param DPDTransaction $transaction
     *
     * @return Attachment
     */
    public function createAttachmentFromDPDTransaction(DPDTransaction $transaction);
}
