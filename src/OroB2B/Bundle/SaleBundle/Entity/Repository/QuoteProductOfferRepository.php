<?php

namespace OroB2B\Bundle\SaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductOfferRepository extends EntityRepository
{
    /**
     * @param array $ids
     * @return QuoteProductOffer[]
     */
    public function getOffersByIds(array $ids)
    {
        $qb = $this->createQueryBuilder('qpf');
        $qb->where($qb->expr()->in('qpf.id', $ids));

        return $qb->getQuery()->getResult();
    }
}
