<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

class PaymentMethodsConfigsRuleRepository extends EntityRepository
{
    /**
     * @param AddressInterface $billingAddress
     * @param string $currency
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getByDestinationAndCurrency(AddressInterface $billingAddress, $currency)
    {
        return $this->getByCurrencyQuery($currency)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->leftJoin('destination.region', 'region')
            ->leftJoin('destination.postalCodes', 'postalCode')
            ->andWhere('destination.country = :country or destination.country is null')
            ->andWhere('region.code = :regionCode or region.code is null')
            ->andWhere('postalCode.name in (:postalCodes) or postalCode.name is null')
            ->setParameter('country', $billingAddress->getCountryIso2())
            ->setParameter('regionCode', $billingAddress->getRegionCode())
            ->setParameter('postalCodes', explode(',', $billingAddress->getPostalCode()))
            ->getQuery()->getResult();
    }

    /**
     * @param string $currency
     *
     * @return QueryBuilder
     */
    private function getByCurrencyQuery($currency)
    {
        $queryBuilder = $this->createQueryBuilder('methodsConfigsRule');

        return $queryBuilder
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->orderBy($queryBuilder->expr()->asc('methodsConfigsRule.id'))
            ->setParameter('currency', $currency);
    }

    /**
     * @param string $currency
     * @return PaymentMethodsConfigsRule[]
     */
    public function getByCurrency($currency)
    {
        return $this->getByCurrencyQuery($currency)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $currency
     * @return PaymentMethodsConfigsRule[]
     */
    public function getByCurrencyWithoutDestination($currency)
    {
        return $this->getByCurrencyQuery($currency)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->andWhere('destination.id is null')
            ->getQuery()->getResult();
    }
}
