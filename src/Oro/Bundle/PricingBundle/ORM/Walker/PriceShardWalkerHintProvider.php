<?php

namespace Oro\Bundle\PricingBundle\ORM\Walker;

use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\DoctrineUtils\ORM\QueryWalkerHintProviderInterface;

class PriceShardWalkerHintProvider implements QueryWalkerHintProviderInterface
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

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
    public function getHints($params)
    {
        return [PriceShardWalker::ORO_PRICING_SHARD_MANAGER => $this->shardManager];
    }
}
