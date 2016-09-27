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
    public function countReferencesForRelation()
    {
        $qb = $this->createQueryBuilder('referenceLexeme');
        $qb->select([
            'referenceLexeme.relationId',
            $qb->expr()->count('referenceLexeme.id') . ' relationCount',
        ])
            ->groupBy('referenceLexeme.relationId');

        return $qb->getQuery()->getScalarResult();
    }
}
