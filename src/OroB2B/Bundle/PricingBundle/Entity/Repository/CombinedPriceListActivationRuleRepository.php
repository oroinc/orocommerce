<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
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
     * @return CombinedPriceListActivationRule[]
     */
    public function getNewActualRules(\DateTime $now)
    {
        $qb = $this->getActualRuleQb($now)
            ->andWhere('rule.active = :activity')
            ->setParameter('activity', false);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param CombinedPriceList $cpl
     * @param \DateTime $now
     * @return \OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule
     */
    public function getActualRuleByCpl(CombinedPriceList $cpl, \DateTime $now)
    {
        $qb = $this->getActualRuleQb($now)
            ->andWhere('rule.fullChainPriceList = :cpl')
            ->setParameter('cpl', $cpl);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \DateTime $now
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getActualRuleQb(\DateTime $now)
    {
        $qb = $this->createQueryBuilder('rule')
            ->andWhere('rule.activateAt <= :now OR rule.activateAt IS NULL')
            ->andWhere('rule.expireAt > :now OR rule.expireAt IS NULL')
            ->setParameter('now', $now);

        return $qb;
    }

    /**
     * @param \DateTime $now
     */
    public function deleteExpiredRules(\DateTime $now)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'rule')
            ->where('rule.expireAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }

    /**
     * @param CombinedPriceListActivationRule[] $rules
     * @param boolean $isActive
     */
    public function updateRulesActivity(array $rules, $isActive)
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder();
        $qb->update($this->getEntityName(), 'rule')
            ->set('rule.active', ':activity')
            ->where($qb->expr()->in('rule.id', ':rules'))
            ->setParameter('activity', $isActive)
            ->setParameter('rules', $rules)
            ->getQuery()->execute();
    }
}
