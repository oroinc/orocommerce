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
        $qb->leftJoin('variant.scopes', 'scopes', Join::WITH)
            ->addSelect('scopes.id as matchedScopeId')
            ->where($qb->expr()->eq('variant.contentBlock', ':contentBlock'))
            ->setParameter('contentBlock', $contentBlock);

        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');

        $result = $qb->getQuery()->getResult(MatchingVariantHydrator::NAME);
        if (!$result) {
            return null;
        }

        return reset($result);
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
