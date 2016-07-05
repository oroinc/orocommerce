<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;

class PriceRuleLexemeRepository extends EntityRepository
{
    /**
     * @param PriceRule[] $rules
     * @return PriceRuleLexeme[]
     */
    public function getLexemesByRules($rules)
    {
        $qb = $this->createQueryBuilder('lexeme');

        return $qb->where($qb->expr()->in('lexeme.priceRule', ':rules'))
            ->setParameter('rules', $rules)
            ->getQuery()
            ->getResult();
    }
}
