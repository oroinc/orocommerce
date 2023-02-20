<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets sharding query hints on config and 'price_list_id = :price_list_id' condition on query.
 */
class EnableShardingOnConfig implements ProcessorInterface
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

        $config = $context->getConfig();
        if (!$config) {
            return;
        }

        $priceListId = PriceListIdContextUtil::getPriceListId($context);

        $queryBuilder->andWhere('e.priceList = :price_list_id')->setParameter('price_list_id', $priceListId);

        $config->addHint('priceList', $priceListId);
        $config->addHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
    }
}
