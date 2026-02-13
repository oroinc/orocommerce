<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets batch operation ID to shared data.
 */
class SetVersionForBatchOperation implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        if (!$context instanceof BatchUpdateContext) {
            return;
        }

        $context->getSharedData()->set('batchOperationId', $context->getOperationId());
    }
}
