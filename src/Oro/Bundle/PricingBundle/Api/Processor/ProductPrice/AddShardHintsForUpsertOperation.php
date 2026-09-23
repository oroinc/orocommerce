<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds product price sharding query hints to the product price configuration
 * when the upsert by fields operation was requested for the "create" action.
 * It allows the upsert operation to find an existing product price in a correct shard.
 */
class AddShardHintsForUpsertOperation implements ProcessorInterface
{
    public function __construct(
        private readonly ShardManager $shardManager,
        private readonly ValueNormalizer $valueNormalizer
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        if (!\is_array($context->get(SetOperationFlags::UPSERT_FLAG))) {
            // the upsert by fields operation was not requested
            return;
        }

        $priceListId = $this->getPriceListId($context);
        if (null === $priceListId) {
            // a price list cannot be resolved; in this case the upsert operation
            // fails with an appropriate validation error
            return;
        }

        $config = $context->getConfig();
        $config->addHint('priceList', $priceListId);
        $config->addHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
    }

    private function getPriceListId(CreateContext $context): ?int
    {
        $priceListId = $context->getRequestData()['priceList']['id'] ?? null;
        if (null === $priceListId) {
            return null;
        }

        try {
            return $this->valueNormalizer->normalizeValue(
                $priceListId,
                DataType::INTEGER,
                $context->getRequestType()
            );
        } catch (\UnexpectedValueException) {
            return null;
        }
    }
}
