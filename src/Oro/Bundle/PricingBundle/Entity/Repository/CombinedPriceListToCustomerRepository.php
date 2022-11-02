<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Repository for ORM entity CombinedPriceListToCustomer
 */
class CombinedPriceListToCustomerRepository extends PriceListToCustomerRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation')
            ->leftJoin(
                PriceListCustomerFallback::class,
                'fallback',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('fallback.customer', 'relation.customer'),
                    $qb->expr()->eq('fallback.website', 'relation.website'),
                    $qb->expr()->eq('fallback.fallback', ':fallback')
                )
            )
            ->leftJoin(
                PriceListToCustomer::class,
                'baseRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('relation.customer', 'baseRelation.customer'),
                    $qb->expr()->eq('relation.website', 'baseRelation.website')
                )
            )
            ->setParameter('fallback', PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY)
            ->where($qb->expr()->isNull('baseRelation.customer'))
            ->andWhere($qb->expr()->isNull('fallback.id'));

        $this->deleteInvalidRelationsByQueryBuilder($qb);
    }

    public function getWebsitesByCombinedPriceList(CombinedPriceList $combinedPriceList): array
    {
        $subQb = $this->createQueryBuilder('relation')
            ->select('relation.id')
            ->where('relation.priceList = :priceList')
            ->andWhere('relation.website = website');

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('website')
            ->from(Website::class, 'website')
            ->where($qb->expr()->exists($subQb->getDQL()))
            ->setParameter('priceList', $combinedPriceList);

        return $qb->getQuery()->getResult();
    }
}
