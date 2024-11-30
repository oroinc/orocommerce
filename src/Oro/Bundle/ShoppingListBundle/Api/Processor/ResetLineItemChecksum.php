<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Once line item has been updated, reset its checksum value for duplicated check.
 */
class ResetLineItemChecksum implements ProcessorInterface
{
    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;

    public function __construct(LineItemChecksumGeneratorInterface $lineItemChecksumGenerator)
    {
        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */
        /** @var LineItem $lineItem */
        $lineItem = $context->getData();

        if ($lineItem->getProduct()) {
            $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
            if ($checksum !== null) {
                $lineItem->setChecksum($checksum);
            }
        }
    }
}
