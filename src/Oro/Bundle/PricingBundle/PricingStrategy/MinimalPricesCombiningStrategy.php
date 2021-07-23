<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\ORM\ShardQueryExecutorInterface;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListIdentifierProviderInterface;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Implements combining price strategy based on PriceList priority and minimal prices
 */
class MinimalPricesCombiningStrategy extends AbstractPriceCombiningStrategy implements
    CombinedPriceListIdentifierProviderInterface
{
    const NAME = 'minimal_prices';

    /**
     * @var ShardManager
     */
    protected $shardManager;

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

    /**
     * {@inheritdoc}
     */
    public function processCombinedPriceListRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $relatedCombinedPriceList
    ) {
        $this->getCombinedProductPriceRepository()->insertMinimalPricesByCombinedPriceList(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $relatedCombinedPriceList
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCombinedPriceListIdentifier(array $priceListsRelations): string
    {
        $key = [];
        // Minimal strategy does not use merge flag, skip it and collect only IDs to create identifier
        foreach ($priceListsRelations as $priceListSequenceMember) {
            $key[] = $priceListSequenceMember->getPriceList()->getId();
        }
        // Minimal prices will be added once from each PL, duplicates should be removed.
        $key = array_unique($key);
        // Minimal prices will be added independently on order so we can sort IDs to get same CPL
        sort($key);

        return md5(implode(self::GLUE, $key));
    }
}
