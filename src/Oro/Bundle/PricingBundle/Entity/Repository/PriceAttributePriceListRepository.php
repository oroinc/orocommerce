<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Entity repository for PriceAttributePriceList entity
 */
class PriceAttributePriceListRepository extends BasePriceListRepository
{
    /**
     * @param $currencies
     *
     * @return QueryBuilder
     */
    public function getAttributesWithCurrenciesQueryBuilder($currencies)
    {
        $qb = $this->createQueryBuilder('price_attribute_price_list')
            ->select(
                'price_attribute_price_list.id',
                'price_attribute_price_list.name',
                'price_attribute_currency.currency'
            );
        $qb->innerJoin(
            'OroPricingBundle:PriceAttributeCurrency',
            'price_attribute_currency',
            Join::WITH,
            $qb->expr()->eq('price_attribute_currency.priceList', 'price_attribute_price_list')
        );

        $qb->andWhere($qb->expr()->in('price_attribute_currency.currency', ':currencies'))
            ->setParameter('currencies', $currencies);

        return $qb;
    }

    /**
     * @return array
     */
    public function getFieldNames()
    {
        $qb = $this->createQueryBuilder('price_attribute_price_list')
            ->select('
                price_attribute_price_list.id,
                price_attribute_price_list.name,
                price_attribute_price_list.fieldName
            ')
            ->orderBy('price_attribute_price_list.id');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getPriceAttributesQueryBuilder()
    {
        return $this->createQueryBuilder('price_attribute_price_list')
            ->select('price_attribute_price_list')
            ->orderBy('price_attribute_price_list.createdAt');
    }
}
