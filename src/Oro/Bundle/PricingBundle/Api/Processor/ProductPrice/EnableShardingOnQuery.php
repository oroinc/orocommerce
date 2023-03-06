<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets sharding query hints and 'price_list_id = :price_list_id' condition on query.
 */
class EnableShardingOnQuery implements ProcessorInterface
{
    private ShardManager $shardManager;

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $queryBuilder = $context->getQuery();
        if (!$queryBuilder instanceof QueryBuilder) {
            return;
        }

        $priceListId = PriceListIdContextUtil::getPriceListId($context);

        $queryBuilder->andWhere('e.priceList = :price_list_id')->setParameter('price_list_id', $priceListId);

        $query = $queryBuilder->getQuery();
        $query->setHint('priceList', $priceListId);
        $query->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);

        $context->setQuery($query);
    }
}
