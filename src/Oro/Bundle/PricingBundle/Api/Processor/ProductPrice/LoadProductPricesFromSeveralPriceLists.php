<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Loads product prices when they belong to several price lists.
 */
class LoadProductPricesFromSeveralPriceLists implements ProcessorInterface
{
    public function __construct(
        private readonly EntitySerializer $entitySerializer,
        private readonly ShardManager $shardManager
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $queryBuilder = $context->getQuery();
        if (!$queryBuilder instanceof QueryBuilder) {
            // unsupported query
            return;
        }

        if ($queryBuilder->getParameters()->containsKey('price_list_id')) {
            // all product prices belong to one price list
            return;
        }

        $priceListIdMap = PriceListIdContextUtil::getPriceListIdMap($context);
        if (!$priceListIdMap) {
            return;
        }

        $data = [];
        /** @var EntityDefinitionConfig $config */
        $config = $context->getConfig();
        try {
            foreach ($priceListIdMap as $priceListId => $productPriceIds) {
                $qb = clone $queryBuilder;
                $qb->andWhere('e.priceList = :price_list_id')->setParameter('price_list_id', $priceListId);
                $config->addHint('priceList', $priceListId);
                $config->addHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
                $data[] = $this->entitySerializer->serialize($qb, $config, $context->getNormalizationContext());
            }
        } finally {
            $config->removeHint('priceList');
            $config->removeHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER);
        }
        $context->setResult(array_merge(...$data));
        // data returned by the EntitySerializer are already normalized
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }
}
