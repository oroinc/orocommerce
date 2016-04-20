<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;

class CombinedPriceListActivationRuleRepository extends EntityRepository
{
    /**
     * @param CombinedPriceList $cpl
     */
    public function deleteRulesByCPL(CombinedPriceList $cpl)
    {
        $qb = $this->_em->createQueryBuilder()->from($this->_entityName, 'rule');
        $qb->delete('rule')
            ->where($qb->expr()->eq('rule.fullChainPriceList', ':cpl'))
            ->setParameter('cpl', $cpl)
            ->getQuery()
            ->execute();
    }
}
