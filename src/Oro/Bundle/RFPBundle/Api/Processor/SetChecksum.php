<?php

namespace Oro\Bundle\RFPBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates and sets the calculated value to the {@see RequestProductItem} checksum.
 */
class SetChecksum implements ProcessorInterface
{
    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;

    public function __construct(LineItemChecksumGeneratorInterface $lineItemChecksumGenerator)
    {
        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var RequestProductItem $lineItem */
        $lineItem = $context->getData();
        $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
        if ($checksum !== null) {
            $lineItem->setChecksum($checksum);
        }
    }
}
