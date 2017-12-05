<?php

namespace Oro\Bundle\SaleBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

class QuoteDemandRepository extends EntityRepository
{
    /**
     * @param Quote $quote
     * @param CustomerUser $customerUser
     *
     * @return QuoteDemand
     */
    public function getQuoteDemandByQuote(Quote $quote, CustomerUser $customerUser)
    {
        $qb = $this->createQueryBuilder('qd');

        return $qb
            ->where(
                $qb->expr()->eq('qd.quote', ':quote'),
                $qb->expr()->eq('qd.customerUser', ':customerUser')
            )
            ->setParameter('quote', $quote)
            ->setParameter('customerUser', $customerUser)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
