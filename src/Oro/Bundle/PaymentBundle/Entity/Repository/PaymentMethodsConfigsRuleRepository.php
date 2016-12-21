<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

class PaymentMethodsConfigsRuleRepository extends EntityRepository
{
    /**
     * @param AddressInterface $billingAddress
     * @param string $currency
     *
     * @return array|PaymentMethodsConfigsRule[]
     */
    public function getByDestinationAndCurrency(AddressInterface $billingAddress, $currency)
    {
        $query = $this->createQueryBuilder('pmcr')
            ->addSelect('methodConfigs')
            ->leftJoin('pmcr.methodConfigs', 'methodConfigs')
            ->where('pmcr.currency = :currency')->setParameter('currency', $currency);

        if ($billingAddress->getCountryIso2()) {
            $query->innerJoin(
                'pmcr.destinations',
                'destination',
                'WITH',
                'destination.country = :country'
            )->setParameter('country', $billingAddress->getCountryIso2());

            if ($billingAddress->getRegionCode()) {
                $query->innerJoin(
                    'destination.region',
                    'region',
                    'WITH',
                    'region.code = :regionCode'
                )->setParameter('regionCode', $billingAddress->getRegionCode());
            }

            if ($billingAddress->getPostalCode()) {
                $query->innerJoin(
                    'destination.postalCodes',
                    'postalCode',
                    'WITH',
                    'postalCode.name in (:postalCodes)'
                )->setParameter('postalCodes', explode(',', $billingAddress->getPostalCode()));
            }
        }

        return $query->getQuery()->execute();
    }
}
