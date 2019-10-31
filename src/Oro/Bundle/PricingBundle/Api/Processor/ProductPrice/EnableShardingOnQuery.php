<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets sharding query hints and 'price_list_id = :price_list_id' condition on query.
 */
class EnableShardingOnQuery implements ProcessorInterface
{
    /** @var ShardManager */
    private $shardManager;

    /**
     * @param ShardManager $shardManager
     */
    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        $context->setQuery($query);
    }
}
