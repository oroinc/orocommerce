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
        $qb = $this->_em->createQueryBuilder();
        $qb->delete()
            ->from($this->_entityName, 'rule')
            ->where($qb->expr()->eq('rule.fullChainPriceList', ':cpl'))
            ->setParameter('cpl', $cpl)
            ->getQuery()->execute();
    }

    /**
     * @param \DateTime $now
     * @return \OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule[]
     */
    public function updateActiveRule(\DateTime $now)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'rule')
            ->where('rule.expireAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        $newActiveRules = $qb = $this->createQueryBuilder('rule')
            ->andWhere('rule.active = :activityTrue')
            ->andWhere('rule.activateAt >= :now')
            ->andWhere('rule.expireAt < :now')
            ->setParameter('now', $now)
            ->setParameter('activityTrue', false)
            ->getQuery()
            ->getResult();

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update($this->getEntityName(), 'rule')
            ->set('rule.active', ':activityTrue')
            ->andWhere('rule.active = :activity')
            ->andWhere('rule.activateAt >= :now')
            ->andWhere('rule.expireAt < :now')
            ->setParameter('now', $now)
            ->setParameter('activityTrue', true)
            ->setParameter('activity', false)
            ->getQuery()
            ->execute();

        return $newActiveRules;
    }
}
