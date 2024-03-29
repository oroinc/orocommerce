<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Hydrator\MatchingVariantHydrator;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Entity repository for Text Content Variant entity.
 */
class TextContentVariantRepository extends EntityRepository
{
    public function getMatchingVariantForBlockByCriteria(
        ContentBlock $contentBlock,
        ScopeCriteria $scopeCriteria
    ): ?TextContentVariant {
        $qb = $this->createQueryBuilder('variant');

        // Since content blocks with restrictions without specific conditions are equivalent to blocks
        // without restrictions, prefer content blocks that are set by default.
        $condition = $qb->expr()->orX(
            $qb->expr()->isNotNull('scopes.website'),
            $qb->expr()->isNotNull('scopes.customerGroup'),
            $qb->expr()->isNotNull('scopes.customer'),
            $qb->expr()->isNotNull('scopes.localization'),
        );

        $qb
            ->leftJoin('variant.scopes', 'scopes', Join::WITH, $condition)
            ->addSelect('scopes.id as matchedScopeId')
            ->where($qb->expr()->eq('variant.contentBlock', ':contentBlock'))
            ->setParameter('contentBlock', $contentBlock)
            ->setMaxResults(1);

        $scopeCriteria->applyWhereWithPriority($qb, 'scopes');

        return $qb->getQuery()->getOneOrNullResult(MatchingVariantHydrator::NAME);
    }

    public function getDefaultContentVariantForContentBlock(
        ContentBlock $contentBlock
    ): ?TextContentVariant {
        $qb = $this->createQueryBuilder('variant');
        $qb->where($qb->expr()->eq('variant.contentBlock', ':contentBlock'))
            ->andWhere($qb->expr()->eq('variant.default', ':isDefault'))
            ->setParameter('contentBlock', $contentBlock)
            ->setParameter('isDefault', true);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
