<?php

namespace Oro\Bundle\PaymentBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Returns payment method config rules by destination, currency and website
 */
class PaymentMethodsConfigsRuleRepository extends EntityRepository
{
    /**
     * @var AclHelper
     */
    private $aclHelper;

    public function setAclHelper(AclHelper $aclHelper)
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
        Website $website = null
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
    public function getByCurrencyAndWebsite(string $currency, Website $website = null): array
    {
        $query = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website);

        return $this->aclHelper->apply($query)->getResult();
    }

    /**
     * @param string       $currency
     * @param Website|null $website
     *
     * @return PaymentMethodsConfigsRule[]
     */
    public function getByCurrencyAndWebsiteWithoutDestination(string $currency, Website $website = null): array
    {
        $query = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->andWhere('destination.id is null');

        return $this->aclHelper->apply($query)->getResult();
    }

    /**
     * @param string $currency
     *
     * @return QueryBuilder
     */
    private function getByCurrencyQueryBuilder($currency): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('methodsConfigsRule');

        return $queryBuilder
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->orderBy($queryBuilder->expr()->asc('methodsConfigsRule.id'))
            ->setParameter('currency', $currency);
    }

    private function getByCurrencyAndWebsiteQueryBuilder(string $currency, Website $website = null): QueryBuilder
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
            ->andWhere($queryBuilder->expr()->eq('methodsConfigsRule.organization', ':organization'))
            ->setParameter('organization', $website->getOrganization());
    }
}
