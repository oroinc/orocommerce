<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Doctrine repository for PaymentMethodsConfigsRule entity.
 */
class PaymentMethodsConfigsRuleRepository extends ServiceEntityRepository
{
    private AclHelper $aclHelper;

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param AddressInterface $billingAddress
     * @param string           $currency
     * @param Website|null     $website
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getByDestinationAndCurrencyAndWebsite(
        AddressInterface $billingAddress,
        string $currency,
        ?Website $website = null
    ): array {
        $queryBuilder = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->leftJoin('methodsConfigsRule.rule', 'rule')
            ->addSelect('rule', 'destination', 'postalCode')
            ->leftJoin('destination.region', 'region')
            ->leftJoin('destination.postalCodes', 'postalCode')
            ->andWhere('destination.country = :country or destination.country is null')
            ->andWhere('region.code = :regionCode or region.code is null')
            ->andWhere('postalCode.name in (:postalCodes) or postalCode.name is null')
            ->setParameter('country', $billingAddress->getCountryIso2())
            ->setParameter('regionCode', $billingAddress->getRegionCode())
            ->setParameter('postalCodes', explode(',', $billingAddress->getPostalCode()));

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * @param string       $currency
     * @param Website|null $website
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getByCurrencyAndWebsite(string $currency, ?Website $website = null): array
    {
        $queryBuilder = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website);

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * @param string       $currency
     * @param Website|null $website
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getByCurrencyAndWebsiteWithoutDestination(string $currency, ?Website $website = null): array
    {
        $queryBuilder = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->andWhere('destination.id is null');

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    private function getByCurrencyQueryBuilder(string $currency): QueryBuilder
    {
        return $this->createQueryBuilder('methodsConfigsRule')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->orderBy('methodsConfigsRule.id')
            ->setParameter('currency', $currency);
    }

    private function getByCurrencyAndWebsiteQueryBuilder(string $currency, ?Website $website): QueryBuilder
    {
        $queryBuilder = $this->getByCurrencyQueryBuilder($currency);

        if ($website === null) {
            return $queryBuilder;
        }

        $queryBuilder
            ->addSelect('websites')
            ->leftJoin('methodsConfigsRule.websites', 'websites')
            ->andWhere('websites.id is null or websites = :website')
            ->setParameter('website', $website);

        if ($website->getOrganization() === null) {
            return $queryBuilder;
        }

        return $queryBuilder
            ->andWhere('methodsConfigsRule.organization = :organization')
            ->setParameter('organization', $website->getOrganization());
    }
}
