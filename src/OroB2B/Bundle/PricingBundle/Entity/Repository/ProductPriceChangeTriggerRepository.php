<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;

class ProductPriceChangeTriggerRepository extends EntityRepository
{
    /**
     * @param ProductPriceChangeTrigger $trigger
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isCreated(ProductPriceChangeTrigger $trigger)
    {
        //product or priceList can be not flushed yet
        if (!$trigger->getProduct()->getId() || !$trigger->getPriceList()->getId()) {
            return false;
        }

        return (bool)$this->createQueryBuilder('cpp')
            ->select('1')
            ->where('cpp.priceList = :priceList')
            ->setParameter('priceList', $trigger->getPriceList())
            ->andWhere('cpp.product = :product')
            ->setParameter('product', $trigger->getProduct())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * @return BufferedQueryResultIterator|ProductPriceChangeTrigger[]
     */
    public function getProductPriceChangeTriggersIterator()
    {
        $qb = $this->createQueryBuilder('productPriceChanges');

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    public function deleteAll()
    {
        $this->createQueryBuilder('productPriceChangeTrigger')
            ->delete('OroB2BPricingBundle:ProductPriceChangeTrigger', 'productPriceChangeTrigger')
            ->getQuery()
            ->execute();
    }
}
