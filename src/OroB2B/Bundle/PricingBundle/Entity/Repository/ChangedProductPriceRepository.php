<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;

class ChangedProductPriceRepository extends EntityRepository
{
    /**
     * @param ChangedProductPrice $changedProductPrice
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isCreated(ChangedProductPrice $changedProductPrice)
    {
        //product or priceList can be not flushed yet
        if (!$changedProductPrice->getProduct()->getId() || !$changedProductPrice->getPriceList()->getId()) {
            return false;
        }

        return (bool)$this->createQueryBuilder('cpp')
            ->select('1')
            ->where('cpp.priceList = :priceList')
            ->setParameter('priceList', $changedProductPrice->getPriceList())
            ->andWhere('cpp.product = :product')
            ->setParameter('product', $changedProductPrice->getProduct())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * @return BufferedQueryResultIterator|ChangedProductPrice[]
     */
    public function getCollectionChangesIterator()
    {
        $qb = $this->createQueryBuilder('productPriceChanges');

        return new BufferedQueryResultIterator($qb->getQuery());
    }
}
