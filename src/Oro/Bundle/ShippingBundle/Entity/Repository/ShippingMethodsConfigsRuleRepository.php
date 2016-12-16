<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

class ShippingMethodsConfigsRuleRepository extends EntityRepository
{
    /**
     * @param string $currency
     * @param string $countryIso2Code
     * @return ShippingMethodsConfigsRule[]
     */
    public function getByCurrencyAndCountry($currency, $countryIso2Code)
    {
        return $this->createQueryBuilder('methodsConfigsRule')
            ->addSelect('methodConfigs', 'typeConfigs')
            ->leftJoin(
                'methodsConfigsRule.destinations',
                'destinations',
                'WITH',
                'destinations.methodsConfigsRule = methodsConfigsRule and destinations.country = :country'
            )
            ->leftJoin('methodsConfigsRule.destinations', 'nullDestinations')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodConfigs.typeConfigs', 'typeConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->andWhere('nullDestinations.id is null or destinations.id is not null')
            ->setParameter('country', $countryIso2Code)
            ->setParameter('currency', $currency)
            ->getQuery()->execute();
    }

    /**
     * @param bool $onlyEnabled
     * @return mixed
     * TODO: refactor in BB-6393
     */
    public function getRulesWithoutShippingMethods($onlyEnabled = false)
    {
        $qb = $this->createQueryBuilder('rule')
            ->select('rule.id')
            ->leftJoin('rule.methodConfigs', 'methodConfigs');
        if ($onlyEnabled) {
            $qb->andWhere('rule.enabled = true');
        }
        return $qb->having('COUNT(methodConfigs.id) = 0')
                  ->groupBy('rule.id')
                  ->getQuery()->execute();
    }

    /**
     * TODO: refactor in BB-6393
     */
    public function disableRulesWithoutShippingMethods()
    {
        $rules = $this->getRulesWithoutShippingMethods(true);
        $enabledRulesIds = array_column($rules, 'id');
        if (0 < count($rules)) {
            $qb = $this->createQueryBuilder('rule');
            $qb->update()
                ->set('rule.enabled', ':newValue')
                ->setParameter('newValue', false)
                ->where($qb->expr()->in('rule.id', ':rules'))
                ->setParameter('rules', $enabledRulesIds)
                ->getQuery()->execute();
        }
    }
}
