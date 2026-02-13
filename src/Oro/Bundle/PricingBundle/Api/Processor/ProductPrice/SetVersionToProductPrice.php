<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the version to the product price based on batch operation ID.
 * Do not set version and trigger mass prices processing if sharding is enabled,
 * because Batch API may update prices for different price lists
 */
class SetVersionToProductPrice implements ProcessorInterface
{
    public function __construct(
        private ShardManager $shardManager
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        if (!$context instanceof CustomizeFormDataContext) {
            return;
        }

        if ($this->shardManager->isShardingEnabled()) {
            return;
        }

        $version = $context->getSharedData()->get('batchOperationId');
        if (null === $version) {
            return;
        }

        $productPrice = $context->getData();
        $productPrice->setVersion($version);
    }
}
