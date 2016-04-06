<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

class AlternativeCheckoutRepository extends EntityRepository
{
    /**
     * @param Quote $quote
     * @return AlternativeCheckout
     */
    public function getCheckoutByQuote(Quote $quote)
    {
        $qb = $this->createQueryBuilder('checkout');

        return $qb->addSelect(['source', 'qd', 'quote'])
            ->innerJoin('checkout.source', 'source')
            ->innerJoin('source.quoteDemand', 'qd')
            ->innerJoin('qd.quote', 'quote')
            ->where($qb->expr()->eq('quote', ':quote'))
            ->setParameter('quote', $quote)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
