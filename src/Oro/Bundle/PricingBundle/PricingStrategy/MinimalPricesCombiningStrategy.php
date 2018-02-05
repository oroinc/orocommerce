<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class MinimalPricesCombiningStrategy extends AbstractPriceCombiningStrategy
{
    const NAME = 'minimal_prices';

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @param Registry $registry
     * @param ShardQueryExecutorInterface $insertFromSelectQueryExecutor
     * @param CombinedPriceListTriggerHandler $triggerHandler
     * @param ShardManager $shardManager
     */
    public function __construct(
        Registry $registry,
        ShardQueryExecutorInterface $insertFromSelectQueryExecutor,
        CombinedPriceListTriggerHandler $triggerHandler,
        ShardManager $shardManager
    ) {
        $this->shardManager = $shardManager;
        parent::__construct($registry, $insertFromSelectQueryExecutor, $triggerHandler);
    }

    /**
     * {@inheritdoc}
     */
    protected function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        array $products = []
    ) {
        $this->getCombinedProductPriceRepository()->insertMinimalPricesByPriceList(
            $this->shardManager,
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $priceListRelation->getPriceList(),
            $products
        );
    }
}
