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
    /**
     * @return array in format
     * [
     *     1 => ['EUR', 'USD'],
     *     5 => ['CAD', 'USD']
     * ]
     * where keys 1 and 5 are pricelist ids to which currencies belong
     */
    public function getCurrenciesIndexedByPricelistIds(): array
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

    public function getPriceListsWithRules(): BufferedQueryResultIteratorInterface
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

    public function updatePriceListsActuality(array $priceLists, bool $actual): void
    {
        if (\count($priceLists)) {
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

    public function getActivePriceListById(int $priceListId): ?PriceList
    {
        return $this->findOneBy(['id' => $priceListId, 'active' => true]);
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByCustomer(Customer $customer, Website $website, bool $isActive = true): ?PriceList
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByCustomerGroup(
        CustomerGroup $customerGroup,
        Website $website,
        bool $isActive = true
    ): ?PriceList {
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
