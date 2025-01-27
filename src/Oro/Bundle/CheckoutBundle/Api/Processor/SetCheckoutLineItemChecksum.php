<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the checksum for checkout line item.
 */
class SetCheckoutLineItemChecksum implements ProcessorInterface
{
    public function __construct(
        private readonly LineItemChecksumGeneratorInterface $lineItemChecksumGenerator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var CheckoutLineItem $lineItem */
        $lineItem = $context->getData();
        $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
        if (null !== $checksum) {
            $lineItem->setChecksum($checksum);
        }
    }
}
