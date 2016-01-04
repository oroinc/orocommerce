<?php


namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class CombinedProductPriceRepository extends EntityRepository
{
    public function insertPricesByPriceListOnMergeEnable(
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        PriceList $priceList
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->createQueryBuilder('pp');
        $qb->leftJoin(
            'OroB2BPricingBundle:CombinedProductPrice',
            'cpp',
            Join::WITH,
            $qb->expr()->andX($qb->expr()->eq('pp.product','cpp.product'),$qb->expr()->eq('cpp.merge')
        );
    }
}