<?php

namespace Oro\Bundle\RFPBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the checksum for request product item.
 */
class SetRequestProductItemChecksum implements ProcessorInterface
{
    public function __construct(
        private readonly LineItemChecksumGeneratorInterface $lineItemChecksumGenerator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var RequestProductItem $lineItem */
        $lineItem = $context->getData();
        $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
        if (null !== $checksum) {
            $lineItem->setChecksum($checksum);
        }
    }
}
