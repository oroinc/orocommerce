<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

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
            ->addSelect('configurations')
            ->leftJoin(
                'rule.destinations',
                'destinations',
                'WITH',
                'destinations.rule = rule and destinations.country = :country'
            )
            ->leftJoin('rule.destinations', 'nullDestinations')
            ->leftJoin('rule.configurations', 'configurations')
            ->where('rule.currency = :currency')
            ->andWhere('rule.enabled = true')
            ->andWhere('nullDestinations.id is null or destinations.id is not null')
            ->setParameter('country', $country)
            ->setParameter('currency', $currency)
            ->orderBy('rule.priority')
            ->addOrderBy('rule.id')
            ->getQuery()->execute();
    }
}
