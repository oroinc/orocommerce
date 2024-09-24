<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Calculates and sets the calculated value to the {@see OrderLineItem} checksum.
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

        /** @var OrderLineItem $lineItem */
        $lineItem = $context->getData();
        $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
        if ($checksum !== null) {
            $lineItem->setChecksum($checksum);
        }
    }
}
