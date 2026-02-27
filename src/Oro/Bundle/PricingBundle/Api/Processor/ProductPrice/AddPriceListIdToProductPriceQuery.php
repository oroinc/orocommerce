<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds filtering by the requested price list to the product price query
 * and adds product price sharding query hints.
 */
class AddPriceListIdToProductPriceQuery implements ProcessorInterface
{
    private ShardManager $shardManager;

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $queryBuilder = $context->getQuery();
        if (!$queryBuilder instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        $priceListId = $this->getPriceListId($context);
        if (null === $priceListId) {
            // no price list or product prices belong to several price lists
            return;
        }

        $queryBuilder->andWhere('e.priceList = :price_list_id')->setParameter('price_list_id', $priceListId);
        /** @var EntityDefinitionConfig $config */
        $config = $context->getConfig();
        $config->addHint('priceList', $priceListId);
        $config->addHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
    }

    private function getPriceListId(Context $context): ?int
    {
        if (PriceListIdContextUtil::hasPriceListId($context)) {
            return PriceListIdContextUtil::getPriceListId($context);
        }

        $priceListIdMap = PriceListIdContextUtil::getPriceListIdMap($context);
        if ($priceListIdMap && \count($priceListIdMap) === 1) {
            return array_key_first($priceListIdMap);
        }

        return null;
    }
}
