<?php

namespace Oro\Bundle\WebCatalogBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

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

        return $qb->getQuery()->getResult();
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
