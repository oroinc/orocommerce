<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class BasePriceListRepository extends EntityRepository
{
    /**
     * @param BasePriceList $priceList
     * @return array|string[]
     */
    public function getInvalidCurrenciesByPriceList(ShardManager $shardManager, BasePriceList $priceList)
    {
        if ($priceList->getId() === null) {
            return [];
        }
        $supportedCurrencies = $priceList->getCurrencies();
        $qb = $this->createQueryBuilder('priceList');
        $qb->select('DISTINCT productPrice.currency')
            ->join('priceList.prices', 'productPrice')
            ->where($qb->expr()->eq('priceList', ':priceList'))
            ->andWhere($qb->expr()->notIn('productPrice.currency', ':supportedCurrencies'))
            ->setParameter('priceList', $priceList)
            ->setParameter('supportedCurrencies', $supportedCurrencies);

        $query = $qb->getQuery();
        $query->useQueryCache(false);
        $query->setHint('priceList', $priceList->getId());
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        $productPrices = $query->getArrayResult();
        $result = [];
        foreach ($productPrices as $productPrice) {
            $result[] = $productPrice['currency'];
        }

        return $result;
    }
}
