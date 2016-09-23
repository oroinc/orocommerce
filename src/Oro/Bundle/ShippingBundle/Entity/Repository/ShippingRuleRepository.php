<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;

class ShippingRuleRepository extends EntityRepository
{
    /**
     * @param string $currency
     * @param Country $country
     * @return ShippingRule[]
     */
    public function getEnabledOrderedRulesByCurrencyAndCountry($currency, Country $country)
    {
        return $this->createQueryBuilder('rule')
            ->addSelect('methodConfigs', 'typeConfigs')
            ->leftJoin(
                'rule.destinations',
                'destinations',
                'WITH',
                'destinations.rule = rule and destinations.country = :country'
            )
            ->leftJoin('rule.destinations', 'nullDestinations')
            ->leftJoin('rule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodConfigs.typeConfigs', 'typeConfigs')
            ->where('rule.currency = :currency')
            ->andWhere('rule.enabled = true')
            ->andWhere('nullDestinations.id is null or destinations.id is not null')
            ->setParameter('country', $country)
            ->setParameter('currency', $currency)
            ->orderBy('rule.priority', Criteria::DESC)
            ->orderBy('rule.id', Criteria::DESC)
            ->addOrderBy('rule.id')
            ->getQuery()->execute();
    }

    public function getRulesWithoutShippingMethods($enabled = false)
    {
        $qb = $this->createQueryBuilder('rule')
            ->select('rule.id')
            ->leftJoin('rule.methodConfigs', 'methodConfigs');
        if ($enabled) {
            $qb->andWhere('rule.enabled = true');
        }
        return $qb->having('COUNT(methodConfigs.id) = 0')
                  ->groupBy('rule.id')
                  ->getQuery()->execute();
    }

    public function disableRulesWithoutShippingMethods()
    {
        $rules = $this->getRulesWithoutShippingMethods(true);
        $final = array_column($rules, 'id');
        if (0 < count($rules)) {
            $qb = $this->createQueryBuilder('rule');
            $qb->update()
                ->set('rule.enabled', ':newValue')
                ->setParameter('newValue', false)
                ->where($qb->expr()->in('rule.id', ':rules'))
                ->setParameter('rules', $final)
                ->getQuery()->execute();
        }
    }
}
