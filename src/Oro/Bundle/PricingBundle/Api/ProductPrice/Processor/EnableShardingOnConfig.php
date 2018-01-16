<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets sharding query hints on config and 'price_list_id = :price_list_id' condition on query
 */
class EnableShardingOnConfig implements ProcessorInterface
{
    /**
     * @var ShardManager
     */
    private $shardManager;

    /**
     * @var PriceListIDContextStorageInterface
     */
    private $priceListIDContextStorage;

    /**
     * @param ShardManager                       $shardManager
     * @param PriceListIDContextStorageInterface $priceListIDContextStorage
     */
    public function __construct(
        ShardManager $shardManager,
        PriceListIDContextStorageInterface $priceListIDContextStorage
    ) {
        $this->shardManager = $shardManager;
        $this->priceListIDContextStorage = $priceListIDContextStorage;
    }

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof Context) {
            return;
        }

        $queryBuilder = $context->getQuery();
        if (!$queryBuilder instanceof QueryBuilder) {
            return;
        }

        $config = $context->getConfig();
        if (!$config) {
            return;
        }

        $priceListID = $this->priceListIDContextStorage->get($context);

        $queryBuilder->andWhere('e.priceList = :price_list_id')->setParameter('price_list_id', $priceListID);

        $config->addHint('priceList', $priceListID);
        $config->addHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
        $config->addHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);
    }
}
