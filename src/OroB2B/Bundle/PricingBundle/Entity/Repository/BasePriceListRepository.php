<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;

class BasePriceListRepository extends EntityRepository
{
    /**
     * @param BasePriceList $priceList
     * @return array|string[]
     */
    public function getInvalidCurrenciesByPriceList(BasePriceList $priceList)
    {
        if ($priceList->getId() === null) {
            return [];
        }
        $supportedCurrencies = $priceList->getCurrencies();
        $qb = $this->createQueryBuilder('priceList');
        $qb->select('DISTINCT productPrice.currency')
            ->join('priceList.prices', 'productPrice')
            ->where($qb->expr()->eq('priceList', ':priceList'))
            ->andWhere($qb->expr()->notIn('productPrice.currency', ':supportedCurrencies'))
            ->setParameter('priceList', $priceList)
            ->setParameter('supportedCurrencies', $supportedCurrencies);

        $productPrices = $qb->getQuery()->getArrayResult();
        $result = [];
        foreach ($productPrices as $productPrice) {
            $result[] = $productPrice['currency'];
        }

        return $result;
    }
}
