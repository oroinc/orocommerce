<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Entity repository for Content Block entity.
 */
class ContentBlockRepository extends EntityRepository
{
    public function getMostSuitableScope(
        ContentBlock $contentBlock,
        ScopeCriteria $criteria
    ): ?Scope {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->from(Scope::class, 'scope')
            ->select('scope')
            ->innerJoin(
                $this->getEntityName(),
                'contentBlock',
                Join::WITH,
                $qb->expr()->isMemberOf('scope', 'contentBlock.scopes')
            )
            ->where($qb->expr()->eq('contentBlock', ':contentBlock'))
            ->setParameter('contentBlock', $contentBlock);

        $criteria->applyWhereWithPriority($qb, 'scope');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
