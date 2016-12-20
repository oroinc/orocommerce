<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
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
        $query = $this->createQueryBuilder('methodsConfigsRule')
            ->addSelect('methodConfigs', 'typeConfigs')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodConfigs.typeConfigs', 'typeConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->setParameter('currency', $currency);
        if ($shippingAddress->getCountryIso2()) {
            $query->innerJoin(
                'methodsConfigsRule.destinations',
                'destination',
                'WITH',
                'destination.country = :country'
            )->setParameter('country', $shippingAddress->getCountryIso2());
            if ($shippingAddress->getRegionCode()) {
                $query->innerJoin(
                    'destination.region',
                    'region',
                    'WITH',
                    'region.code = :regionCode'
                )->setParameter('regionCode', $shippingAddress->getRegionCode());
            }
            if ($shippingAddress->getPostalCode()) {
                $query->innerJoin(
                    'destination.postalCodes',
                    'postalCode',
                    'WITH',
                    'postalCode.name in (:postalCodes)'
                )->setParameter('postalCodes', explode(',', $shippingAddress->getPostalCode()));
            }
        }

        return $query->getQuery()->execute();
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
