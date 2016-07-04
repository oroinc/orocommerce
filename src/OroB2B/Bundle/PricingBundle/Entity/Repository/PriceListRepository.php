<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListRepository extends EntityRepository
{
    /**
     * @todo: should be dropped in scope of BB-1858
     */
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

    /**
     * @todo: should be dropped in scope of BB-1858
     * @param PriceList $priceList
     */
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
     * @todo: should be dropped in scope of BB-1858
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
}
