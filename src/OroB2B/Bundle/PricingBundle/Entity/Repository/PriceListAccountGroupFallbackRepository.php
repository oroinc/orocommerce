<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;

class PriceListAccountGroupFallbackRepository extends EntityRepository
{
    /**
     * @param int $websiteId
     * @return BufferedQueryResultIterator|array
     */
    public function getAccountIdentityByWebsite($websiteId)
    {
        /** @var PriceListAccountFallbackRepository $accountFallbackRepository */
        $accountFallbackRepository = $this->getEntityManager()
            ->getRepository('OroB2BPricingBundle:PriceListAccountFallback');
        $qb = $accountFallbackRepository->getBaseQbForFallback($websiteId);

        $qb->leftJoin(
            'OroB2BPricingBundle:PriceListAccountGroupFallback',
            'accountGroupFallback',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('account.group', 'accountGroupFallback.accountGroup'),
                $qb->expr()->eq('accountGroupFallback.website', ':website')
            )
        )
        ->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('accountGroupFallback.id'),
                $qb->expr()->eq('accountGroupFallback.fallback', ':fallbackGroup')
            )
        )
        ->setParameter('website', $websiteId)
        ->setParameter('fallbackGroup', PriceListAccountGroupFallback::WEBSITE);
        
        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        return $iterator;
    }
}
