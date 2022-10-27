<?php

namespace Oro\Bundle\PricingBundle\ORM\Walker;

use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\DoctrineUtils\ORM\QueryWalkerHintProviderInterface;

/**
 * Provide ORO_PRICING_SHARD_MANAGER hint with ShardManager instance set
 */
class PriceShardWalkerHintProvider implements QueryWalkerHintProviderInterface
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getHints($params)
    {
        return [PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER => $this->shardManager];
    }
}
