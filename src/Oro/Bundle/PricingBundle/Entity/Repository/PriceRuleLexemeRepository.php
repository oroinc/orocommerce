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

    /**
     * @return array
     */
    public function getRelationIds()
    {
        $qb = $this->createQueryBuilder('referenceLexeme');
        $qb->select('referenceLexeme.relationId')->distinct();
        $result = $qb->getQuery()->getScalarResult();
        return array_map(function ($value) {
            return $value['relationId'];
        }, $result);
    }
}
