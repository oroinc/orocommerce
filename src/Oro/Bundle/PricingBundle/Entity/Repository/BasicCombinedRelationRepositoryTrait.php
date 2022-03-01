<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;

/**
 * Update combined price lists to actual state based on given activation rules.
 *
 * @method EntityManager getEntityManager()
 * @method string getEntityName()
 */
trait BasicCombinedRelationRepositoryTrait
{
    /**
     * @param CombinedPriceListActivationRule[] $rules
     */
    public function updateActuality(array $rules)
    {
        $updated = 0;
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update($this->getEntityName(), 'relation')
            ->set('relation.priceList', ':actualPriceList')
            ->where($qb->expr()->eq('relation.fullChainPriceList', ':fullPriceList'))
            ->andWhere($qb->expr()->neq('relation.priceList', ':actualPriceList'));
        foreach ($rules as $rule) {
            $updated += $qb->setParameter('actualPriceList', $rule->getCombinedPriceList())
                ->setParameter('fullPriceList', $rule->getFullChainPriceList())
                ->getQuery()
                ->execute();
        }

        return $updated;
    }

    protected function deleteInvalidRelationsByQueryBuilder(QueryBuilder $qb): void
    {
        $result = $qb->getQuery()->getScalarResult();
        $invalidRelationIds = array_map('current', $result);
        if ($invalidRelationIds) {
            $qb = $this->createQueryBuilder('relation');
            $qb->delete()->where($qb->expr()->in('relation.id', ':invalidRelationIds'))
                ->setParameter(':invalidRelationIds', $invalidRelationIds);
            $qb->getQuery()->execute();
        }
    }
}
