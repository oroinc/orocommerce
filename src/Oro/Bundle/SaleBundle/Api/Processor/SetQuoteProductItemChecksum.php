<?php

namespace Oro\Bundle\SaleBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the checksum for quote product offer and request.
 */
class SetQuoteProductItemChecksum implements ProcessorInterface
{
    public function __construct(
        private readonly LineItemChecksumGeneratorInterface $checksumGenerator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var BaseQuoteProductItem $quoteProductItem */
        $quoteProductItem = $context->getData();
        $checksum = $this->checksumGenerator->getChecksum($quoteProductItem);
        if (null !== $checksum) {
            $quoteProductItem->setChecksum($checksum);
        }
    }
}
