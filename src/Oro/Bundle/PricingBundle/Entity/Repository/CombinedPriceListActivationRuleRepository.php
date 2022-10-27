<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationHelper;

/**
 * Doctrine Entity repository for CombinedPriceListActivationRule entity
 */
class CombinedPriceListActivationRuleRepository extends EntityRepository
{
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
        return $this->getNewActualRulesForCombinedPriceList($now);
    }

    public function getNewActualRulesForCombinedPriceList(\DateTime $now, CombinedPriceList $fullCpl = null)
    {
        $qb = $this->getActualRuleQb($now)
            ->andWhere('rule.active = :activity')
            ->setParameter('activity', false);

        if ($fullCpl) {
            $qb->andWhere('rule.fullChainPriceList = :fullCpl')
                ->setParameter('fullCpl', $fullCpl);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param CombinedPriceList $cpl
     * @param \DateTime $now
     * @return CombinedPriceListActivationRule|null
     */
    public function getActualRuleByCpl(CombinedPriceList $cpl, \DateTime $now)
    {
        $qb = $this->getActualRuleQb($now)
            ->andWhere('rule.fullChainPriceList = :cpl')
            ->setParameter('cpl', $cpl);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getActiveRuleByScheduledCpl(
        CombinedPriceList $cpl,
        \DateTime $activateDate
    ): ?CombinedPriceListActivationRule {
        $qb = $this->getActualRuleQb($activateDate)
            ->andWhere('rule.combinedPriceList = :cpl')
            ->setParameter('cpl', $cpl);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function hasActivationRules(CombinedPriceList $cpl): bool
    {
        $existenceQB = $this->createQueryBuilder('rule')
            ->select('rule.id')
            ->where('rule.fullChainPriceList = :cpl')
            ->setMaxResults(1)
            ->setParameter('cpl', $cpl);

        return (bool)$existenceQB->getQuery()->getOneOrNullResult();
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
            ->setParameter('now', $now, Types::DATETIME_MUTABLE);

        return $qb;
    }

    public function deleteExpiredRules(\DateTime $now)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'rule')
            ->where('rule.expireAt < :now')
            ->setParameter('now', $now, Types::DATETIME_MUTABLE)
            ->getQuery()
            ->execute();
    }

    public function deleteUnlinkedRules(array $exceptPriceLists = []): void
    {
        $brokenRulesQB = $this->createQueryBuilder('r')
            ->select('r.id');
        // Find All rules with full CPLs that are not used for any relation as full CPL
        foreach (CombinedPriceListRelationHelper::RELATIONS as $alias => $entityName) {
            $brokenRulesQB->leftJoin(
                $entityName,
                $alias,
                Join::WITH,
                $brokenRulesQB->expr()->eq($alias . '.fullChainPriceList', 'r.fullChainPriceList')
            );
            $brokenRulesQB->andWhere($brokenRulesQB->expr()->isNull($alias . '.priceList'));
        }

        if ($exceptPriceLists) {
            $brokenRulesQB
                ->andWhere($brokenRulesQB->expr()->notIn('r.fullChainPriceList', ':exceptPriceLists'))
                ->setParameter('exceptPriceLists', $exceptPriceLists);
        }

        $ruleIds = array_column($brokenRulesQB->getQuery()->getScalarResult(), 'id');
        if ($ruleIds) {
            $deleteQB = $this->getEntityManager()->createQueryBuilder();
            $deleteQB->delete(CombinedPriceListActivationRule::class, 'r')
                ->where($deleteQB->expr()->in('r.id', ':ids'))
                ->setParameter('ids', $ruleIds);

            $deleteQB->getQuery()->execute();
        }
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
