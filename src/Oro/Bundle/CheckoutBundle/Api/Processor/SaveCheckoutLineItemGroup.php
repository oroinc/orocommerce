<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Processor\Update\SaveEntity;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Api\Model\CheckoutLineItemGroup;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Saves changed checkout line item group into the database.
 */
class SaveCheckoutLineItemGroup implements ProcessorInterface
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly FlushDataHandlerInterface $flushDataHandler
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        if ($context->isProcessed(SaveEntity::OPERATION_NAME)) {
            // the entity was already saved
            return;
        }

        $group = $context->getResult();
        if (!$group instanceof CheckoutLineItemGroup) {
            return;
        }

        $this->flushDataHandler->flushData(
            $this->doctrineHelper->getEntityManagerForClass(Checkout::class),
            new FlushDataHandlerContext([$context], $context->getSharedData())
        );

        $context->setProcessed(SaveEntity::OPERATION_NAME);
    }
}
