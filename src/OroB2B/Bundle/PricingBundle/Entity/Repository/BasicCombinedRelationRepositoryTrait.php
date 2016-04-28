<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;

/**
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
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update($this->getEntityName(), 'relation')
            ->set('relation.priceList', ':actualPriceList')
            ->where('relation.fullChainPriceList = :fullPriceList');
        foreach ($rules as $rule) {
            $qb->setParameter('actualPriceList', $rule->getCombinedPriceList())
                ->setParameter('fullPriceList', $rule->getFullChainPriceList())
                ->getQuery()
                ->execute();
        }
    }
}
