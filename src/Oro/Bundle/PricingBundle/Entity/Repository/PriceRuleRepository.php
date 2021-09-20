<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ORM entity repository for PriceRule entity.
 */
class PriceRuleRepository extends EntityRepository
{
    public function getRuleIds(): array
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('pr.id')
            ->from($this->getEntityName(), 'pr');

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }
}
