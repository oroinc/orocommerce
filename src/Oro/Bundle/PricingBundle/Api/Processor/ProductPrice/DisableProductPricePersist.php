<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Create\PersistEntity;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Disables persist ProductPrice entity in entity manager when price sharding enabled.
 */
class DisableProductPricePersist implements ProcessorInterface
{
    public function __construct(
        private readonly ShardManager $shardManager
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($this->shardManager->isShardingEnabled()) {
            $context->setProcessed(PersistEntity::OPERATION_NAME);
        }
    }
}
