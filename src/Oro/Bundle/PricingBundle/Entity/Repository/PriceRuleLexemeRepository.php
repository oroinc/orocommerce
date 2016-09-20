<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class PriceRuleLexemeRepository extends EntityRepository
{
    /**
     * @param PriceList $priceList
     */
    public function deleteByPriceList(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('lexeme');

        $qb->delete()
            ->where($qb->expr()->eq('lexeme.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);
        
        $qb->getQuery()->execute();
    }
}
