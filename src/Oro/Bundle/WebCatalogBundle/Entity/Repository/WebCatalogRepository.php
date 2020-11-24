<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * This class represents a custom repository for the WebCatalog entity
 * It contains useful methods to access data in the Database
 */
class WebCatalogRepository extends EntityRepository
{
    /**
     * @param WebCatalog $webCatalog
     * @return QueryBuilder
     */
    public function getUsedScopesQueryBuilder(WebCatalog $webCatalog)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Scope::class, 'scope')
            ->select('scope')
            ->where($qb->expr()->eq('scope.webCatalog', ':webCatalog'))
            ->setParameter('webCatalog', $webCatalog);

        return $qb;
    }

    /**
     * @param WebCatalog $webCatalog
     * @return Scope[]
     */
    public function getUsedScopes(WebCatalog $webCatalog)
    {
        $qb = $this->getUsedScopesQueryBuilder($webCatalog);
        $qb->innerJoin(
            ContentVariant::class,
            'variant',
            Join::WITH,
            $qb->expr()->isMemberOf('scope', 'variant.scopes')
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param WebCatalog $webCatalog
     *
     * @return int[]
     */
    public function getUsedScopesIds(WebCatalog $webCatalog): array
    {
        $qb = $this->getUsedScopesQueryBuilder($webCatalog);

        $qb->select('scope.id as scopeId');

        $query = $qb->getQuery();

        $identifierHydrationMode = 'IdentifierHydrator';
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);
        $scopeIds = $qb->getQuery()->getResult($identifierHydrationMode);

        return $scopeIds;
    }

    /**
     * @param WebCatalog $webCatalog
     * @param ScopeCriteria $scopeCriteria
     * @return Scope[]
     */
    public function getMatchingScopes(WebCatalog $webCatalog, ScopeCriteria $scopeCriteria)
    {
        $qb = $this->getUsedScopesQueryBuilder($webCatalog);
        $scopeCriteria->applyWhereWithPriority($qb, 'scope');

        return $qb->getQuery()->getResult();
    }
}
