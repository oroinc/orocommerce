<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Returns shipping method config rules by destination, currency and website
 */
class ShippingMethodsConfigsRuleRepository extends EntityRepository
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
     * @param AddressInterface $address
     * @param string           $currency
     * @param Website          $website
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getByDestinationAndCurrencyAndWebsite(
        AddressInterface $address,
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
            ->setParameter('country', $address->getCountryIso2())
            ->setParameter('regionCode', $address->getRegionCode())
            ->setParameter('postalCodes', explode(',', $address->getPostalCode()));

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * @param string  $currency
     * @param Website $website
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getByCurrencyAndWebsite(string $currency, Website $website = null): array
    {
        $query = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website);

        return $this->aclHelper->apply($query)->getResult();
    }

    /**
     * @param string  $currency
     * @param Website $website
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getByCurrencyAndWebsiteWithoutDestination(string $currency, Website $website = null): array
    {
        $query = $this->getByCurrencyAndWebsiteQueryBuilder($currency, $website)
            ->leftJoin('methodsConfigsRule.destinations', 'destination')
            ->andWhere('destination.id is null');

        return $this->aclHelper->apply($query)->getResult();
    }

    /**
     * @param bool $onlyEnabled
     *
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
            ->getQuery()->execute();
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
     * @param string $method
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getRulesByMethod($method)
    {
        $qb = $this->getRulesByMethodQueryBuilder($method);

        return $this->aclHelper->apply($qb)->getResult();
    }

    /**
     * @param string $method
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getEnabledRulesByMethod($method)
    {
        $qb = $this->getRulesByMethodQueryBuilder($method)
            ->addSelect('rule')
            ->innerJoin('methodsConfigsRule.rule', 'rule', Expr\Join::WITH, 'rule.enabled = true');

        return $this->aclHelper->apply($qb)->getResult();
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
            ->addSelect('methodConfigs', 'typeConfigs')
            ->leftJoin('methodsConfigsRule.methodConfigs', 'methodConfigs')
            ->leftJoin('methodConfigs.typeConfigs', 'typeConfigs')
            ->where('methodsConfigsRule.currency = :currency')
            ->setParameter('currency', $currency)
            ->orderBy($queryBuilder->expr()->asc('methodsConfigsRule.id'));
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

    /**
     * @param string $method
     *
     * @return QueryBuilder
     */
    private function getRulesByMethodQueryBuilder($method)
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
