<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Repository for ORM entity CombinedPriceListToCustomerGroup
 */
class CombinedPriceListToCustomerGroupRepository extends PriceListToCustomerGroupRepository
{
    use BasicCombinedRelationRepositoryTrait;

    public function deleteInvalidRelations()
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->select('relation.id')
            ->leftJoin(
                PriceListCustomerGroupFallback::class,
                'fallback',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('fallback.customerGroup', 'relation.customerGroup'),
                    $qb->expr()->eq('fallback.website', 'relation.website'),
                    $qb->expr()->eq('fallback.fallback', ':fallback')
                )
            )
            ->leftJoin(
                PriceListToCustomerGroup::class,
                'baseRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('relation.customerGroup', 'baseRelation.customerGroup'),
                    $qb->expr()->eq('relation.website', 'baseRelation.website')
                )
            )
            ->setParameter('fallback', PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY)
            ->where($qb->expr()->isNull('baseRelation.customerGroup'))
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
            ->where($qb->expr()->exists(
                $subQb->getDQL()
            ))
            ->setParameter('priceList', $combinedPriceList);

        return $qb->getQuery()->getResult();
    }
}
