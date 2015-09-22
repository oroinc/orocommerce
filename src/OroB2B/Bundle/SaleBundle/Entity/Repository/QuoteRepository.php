<?php

namespace OroB2B\Bundle\SaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteRepository extends EntityRepository
{
    /**
     * @param int $id
     * @return Quote
     *
     * @link http://www.doctrine-project.org/jira/browse/DDC-2536 setFetchMode doesn't work here
     */
    public function getQuote($id)
    {
        $qb = $this->createQueryBuilder('q');

        try {
            return $qb
                ->select(['q', 'quoteProducts', 'quoteProductOffers'])
                ->leftJoin('q.quoteProducts', 'quoteProducts')
                ->leftJoin('quoteProducts.quoteProductOffers', 'quoteProductOffers')
                ->where($qb->expr()->eq('q.id', ':id'))
                ->setParameter('id', (int)$id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return null;
    }
}
