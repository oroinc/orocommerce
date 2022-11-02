<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Base implementation of Price entity repositories.
 */
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
        $query->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $shardManager);

        $productPrices = $query->getArrayResult();
        $result = [];
        foreach ($productPrices as $productPrice) {
            $result[] = $productPrice['currency'];
        }

        return $result;
    }
}
