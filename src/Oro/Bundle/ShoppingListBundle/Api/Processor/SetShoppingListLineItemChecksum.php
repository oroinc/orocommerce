<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the checksum for shopping list line item.
 */
class SetShoppingListLineItemChecksum implements ProcessorInterface
{
    public function __construct(
        private readonly LineItemChecksumGeneratorInterface $lineItemChecksumGenerator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var LineItem $lineItem */
        $lineItem = $context->getData();
        $checksum = $this->lineItemChecksumGenerator->getChecksum($lineItem);
        if (null !== $checksum) {
            $lineItem->setChecksum($checksum);
        }
    }
}
