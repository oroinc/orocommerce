<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
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
        return (bool)$this->createQueryBuilder('cpp')
            ->select()
            ->where('cpp.priceList = :priceList')
            ->setParameter('priceList', $changedProductPrice->getPriceList())
            ->andWhere('cpp.product = :product')
            ->setParameter('product', $changedProductPrice->getProduct())
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SCALAR);
    }
}
