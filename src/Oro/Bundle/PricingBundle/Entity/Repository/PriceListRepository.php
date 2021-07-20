<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Repository for PriceList ORM entity.
 */
class PriceListRepository extends BasePriceListRepository
{
    protected function dropDefaults()
    {
        $qb = $this->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':defaultValue')
            ->setParameter('defaultValue', false)
            ->where($qb->expr()->eq('pl.default', ':oldValue'))
            ->setParameter('oldValue', true)
            ->getQuery()
            ->execute();
    }

    public function setDefault(PriceList $priceList)
    {
        $this->dropDefaults();

        $qb = $this->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':newValue')
            ->setParameter('newValue', true)
            ->where($qb->expr()->eq('pl', ':entity'))
            ->setParameter('entity', $priceList)
            ->getQuery()
            ->execute();
    }

    /**
     * @return PriceList
     */
    public function getDefault()
    {
        $qb = $this->createQueryBuilder('pl');

        return $qb
            ->where($qb->expr()->eq('pl.default', ':default'))
            ->setParameter('default', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array in format
     * [
     *     1 => ['EUR', 'USD'],
     *     5 => ['CAD', 'USD']
     * ]
     * where keys 1 and 5 are pricelist ids to which currencies belong
     */
    public function getCurrenciesIndexedByPricelistIds()
    {
        $qb = $this->createQueryBuilder('priceList');

        $currencyInfo = $qb
            ->select('priceList.id, priceListCurrency.currency')
            ->join('priceList.currencies', 'priceListCurrency')
            ->orderBy('priceListCurrency.currency')
            ->getQuery()
            ->getArrayResult();

        $currencies = [];
        foreach ($currencyInfo as $info) {
            $currencies[$info['id']][] = $info['currency'];
        }

        return $currencies;
    }

    /**
     * @return BufferedQueryResultIteratorInterface
     */
    public function getPriceListsWithRules()
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb->select('priceList, priceRule')
            ->leftJoin('priceList.priceRules', 'priceRule')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->isNotNull('priceList.productAssignmentRule'),
                    $qb->expr()->isNotNull('priceRule.id')
                )
            )
            ->orderBy('priceList.id, priceRule.priority');

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param array|PriceList[] $priceLists
     * @param bool $actual
     */
    public function updatePriceListsActuality(array $priceLists, $actual)
    {
        if (count($priceLists)) {
            $qb = $this->_em->createQueryBuilder();
            $qb->update($this->_entityName, 'priceList');
            $qb->set('priceList.actual', ':actual')
                ->where($qb->expr()->in('priceList.id', ':priceLists'))
                ->andWhere($qb->expr()->neq('priceList.actual', ':actual'))
                ->setParameter('actual', $actual)
                ->setParameter('priceLists', $priceLists);
            $qb->getQuery()->execute();
        }
    }

    /**
     * @param int $priceListId
     * @return PriceList|null
     */
    public function getActivePriceListById(int $priceListId)
    {
        return $this->findOneBy(['id' => $priceListId, 'active' => true]);
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @param bool $isActive
     * @return null|PriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByCustomer(Customer $customer, Website $website, $isActive = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                PriceListToCustomer::class,
                'priceListToCustomer',
                Join::WITH,
                'priceListToCustomer.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToCustomer.customer', ':customer'))
            ->andWhere($qb->expr()->eq('priceListToCustomer.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->setParameter('customer', $customer)
            ->setParameter('website', $website)
            ->setParameter('active', $isActive)
            ->orderBy('priceListToCustomer.sortOrder')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @param bool $isActive
     * @return null|PriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByCustomerGroup(CustomerGroup $customerGroup, Website $website, bool $isActive = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                PriceListToCustomerGroup::class,
                'priceListToCustomerGroup',
                Join::WITH,
                'priceListToCustomerGroup.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToCustomerGroup.customerGroup', ':customerGroup'))
            ->andWhere($qb->expr()->eq('priceListToCustomerGroup.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->setParameter('customerGroup', $customerGroup)
            ->setParameter('website', $website)
            ->setParameter('active', $isActive)
            ->orderBy('priceListToCustomerGroup.sortOrder')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
