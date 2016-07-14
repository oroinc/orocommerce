<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

class PriceAttributePriceListRepository extends EntityRepository
{
    /**
     * @param array $currencies
     * @return array
     */
    public function getAttributesWithCurrencies($currencies)
    {
        $qb = $this->createQueryBuilder('price_attribute_price_list')
            ->select(
                'price_attribute_price_list.id',
                'price_attribute_price_list.name',
                'price_attribute_currency.currency'
            );
        $qb->innerJoin(
            'OroB2BPricingBundle:PriceAttributeCurrency',
            'price_attribute_currency',
            Join::WITH,
            $qb->expr()->eq('price_attribute_currency.priceList', 'price_attribute_price_list')
        );

        $qb->andWhere($qb->expr()->in('price_attribute_currency.currency', ':currencies'))
            ->setParameter('currencies', $currencies);


        return $qb->getQuery()->getResult();
    }
}
