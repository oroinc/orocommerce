<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

class ShippingMethodsConfigsRuleRepository extends EntityRepository
{
    /**
     * @param AddressInterface $shippingAddress
     * @param string $currency
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getByDestinationAndCurrency(AddressInterface $shippingAddress, $currency)
    {
        return $this->getByCurrencyQuery($currency)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->leftJoin('destination.region', 'region')
            ->leftJoin('destination.postalCodes', 'postalCode')
            ->andWhere('destination.country = :country or destination.country is null')
            ->andWhere('region.code = :regionCode or region.code is null')
            ->andWhere('postalCode.name in (:postalCodes) or postalCode.name is null')
            ->setParameter('country', $shippingAddress->getCountryIso2())
            ->setParameter('regionCode', $shippingAddress->getRegionCode())
            ->setParameter('postalCodes', explode(',', $shippingAddress->getPostalCode()))
            ->getQuery()->execute()
        ;
    }

    /**
     * @param string $currency
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getByCurrencyWithoutDestination($currency)
    {
        return $this->getByCurrencyQuery($currency)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->andWhere('destination.id is null')
            ->getQuery()
            ->execute();
    }

    /**
     * @param bool $onlyEnabled
     * @return mixed
     */
    public function getRulesWithoutShippingMethods($onlyEnabled = false)
    {
        $qb = $this->createQueryBuilder('methodsConfigsRule')
            ->select('rule.id')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodsConfigsRule.rule', 'rule');
        if ($onlyEnabled) {
            $qb->andWhere('rule.enabled = true');
        }
        return $qb
            ->having('COUNT(methodConfigs.id) = 0')
            ->groupBy('rule.id')
            ->getQuery()->execute()
        ;
    }

    public function disableRulesWithoutShippingMethods()
    {
        $rules = $this->getRulesWithoutShippingMethods(true);
        if (0 < count($rules)) {
            $enabledRulesIds = array_column($rules, 'id');
            $qb = $this->createQueryBuilder('methodsConfigsRule');
            $qb->update('OroRuleBundle:Rule', 'rule')
                ->set('rule.enabled', ':newValue')
                ->setParameter('newValue', false)
                ->where($qb->expr()->in('rule.id', ':rules'))
                ->setParameter('rules', $enabledRulesIds)
                ->getQuery()->execute();
        }
    }

    /**
     * @param string $currency
     * @return QueryBuilder
     */
    private function getByCurrencyQuery($currency)
    {
        return $this->createQueryBuilder('methodsConfigsRule')
            ->addSelect('methodConfigs', 'typeConfigs')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodConfigs.typeConfigs', 'typeConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->setParameter('currency', $currency)
        ;
    }
}
