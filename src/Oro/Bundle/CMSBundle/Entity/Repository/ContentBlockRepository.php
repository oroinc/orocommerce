<?php

namespace Oro\Bundle\CMSBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

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

    /**
     * Returns an alias for enabled content block by id and organization id
     */
    public function getContentBlockAliasById(int $id, AclHelper $aclHelper): ?string
    {
        $qb = $this->createQueryBuilder('cb');
        $qb->select('cb.alias as alias')
            ->where(
                $qb->expr()->eq('cb.id', ':id'),
                $qb->expr()->eq('cb.enabled', ':enabled'),
            )
            ->setParameter('id', $id, Types::INTEGER)
            ->setParameter('enabled', true, Types::BOOLEAN);

        return $aclHelper->apply($qb)->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }
}
