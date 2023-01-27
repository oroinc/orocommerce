<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Doctrine repository for ShippingMethodsConfigsRule entity.
 */
class ShippingMethodsConfigsRuleRepository extends ServiceEntityRepository
{
    private AclHelper $aclHelper;

    public function setAclHelper(AclHelper $aclHelper): void
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param AddressInterface $address
     * @param string           $currency
     * @param Website|null     $website
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getByDestinationAndCurrencyAndWebsite(
        AddressInterface $address,
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
            ->setParameter('country', $address->getCountryIso2())
            ->setParameter('regionCode', $address->getRegionCode())
            ->setParameter('postalCodes', explode(',', $address->getPostalCode()));

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * @param string       $currency
     * @param Website|null $website
     *
     * @return ShippingMethodsConfigsRule[]
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
     * @return ShippingMethodsConfigsRule[]
     */
    public function getByCurrencyAndWebsiteWithoutDestination(string $currency, ?Website $website = null): array
    {
        $queryBuilder = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->andWhere('destination.id is null');

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    public function disableRulesWithoutShippingMethods(): void
    {
        $rules = $this->createQueryBuilder('methodsConfigsRule')
            ->select('rule.id')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodsConfigsRule.rule', 'rule')
            ->andWhere('rule.enabled = true')
            ->having('COUNT(methodConfigs.id) = 0')
            ->groupBy('rule.id')
            ->getQuery()
            ->getArrayResult();
        if ($rules) {
            $this->createQueryBuilder('methodsConfigsRule')
                ->update(Rule::class, 'rule')
                ->set('rule.enabled', ':enabled')
                ->setParameter('enabled', false)
                ->where('rule.id IN (:rules)')
                ->setParameter('rules', array_column($rules, 'id'))
                ->getQuery()
                ->execute();
        }
    }

    /**
     * @param string $method
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getRulesByMethod(string $method): array
    {
        $queryBuilder = $this->getRulesByMethodQueryBuilder($method);

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * @param string $method
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getEnabledRulesByMethod(string $method): array
    {
        $queryBuilder = $this->getRulesByMethodQueryBuilder($method)
            ->addSelect('rule')
            ->innerJoin('methodsConfigsRule.rule', 'rule', Expr\Join::WITH, 'rule.enabled = true');

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    private function getByCurrencyQueryBuilder(string $currency): QueryBuilder
    {
        return $this->createQueryBuilder('methodsConfigsRule')
            ->addSelect('methodConfigs', 'typeConfigs')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodConfigs.typeConfigs', 'typeConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->setParameter('currency', $currency)
            ->orderBy('methodsConfigsRule.id');
    }

    private function getByCurrencyAndWebsiteQueryBuilder(string $currency, ?Website $website): QueryBuilder
    {
        $queryBuilder = $this->getByCurrencyQueryBuilder($currency);

        if (null !== $website) {
            $queryBuilder
                ->addSelect('websites')
                ->leftJoin('methodsConfigsRule.websites', 'websites')
                ->andWhere('websites.id is null or websites = :website')
                ->setParameter('website', $website);

            if (null !== $website->getOrganization()) {
                $queryBuilder
                    ->andWhere('methodsConfigsRule.organization = :organization')
                    ->setParameter('organization', $website->getOrganization());
            }
        }

        return $queryBuilder;
    }

    private function getRulesByMethodQueryBuilder(string $method): QueryBuilder
    {
        return $this->createQueryBuilder('methodsConfigsRule')
            ->addSelect('destination', 'postalCode')
            ->innerJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->leftJoin('destination.postalCodes', 'postalCode')
            ->where('methodConfigs.method = :method')
            ->setParameter('method', $method);
    }
}
