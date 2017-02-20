<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class SlugRepository extends EntityRepository
{
    /**
     * @param string $url
     * @param ScopeCriteria $scopeCriteria
     * @return Slug|null
     */
    public function getSlugByUrlAndScopeCriteria($url, ScopeCriteria $scopeCriteria)
    {
        $qb = $this->createQueryBuilder('slug');
        $qb->leftJoin('slug.scopes', 'scopes', Join::WITH)
            ->addSelect('scopes.id as matchedScopeId')
            ->where($qb->expr()->eq('slug.urlHash', ':urlHash'))
            ->andWhere($qb->expr()->eq('slug.url', ':url'))
            ->setParameter('urlHash', md5($url))
            ->setParameter('url', $url);

        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');

        $results = $qb->getQuery()->getResult();
        foreach ($results as $result) {
            /** @var Slug $slug */
            $slug = $result[0];
            $matchedScopeId = $result['matchedScopeId'];
            if ($matchedScopeId || $slug->getScopes()->isEmpty()) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * @param Scope $scope
     * @return bool
     */
    public function isScopeAttachedToSlug(Scope $scope)
    {
        $qb = $this->getUsedScopesQueryBuilder();
        $qb->select('scope.id')
            ->andWhere($qb->expr()->eq('scope', ':scope'))
            ->setParameter('scope', $scope);
        return (bool)$qb->getQuery()->getScalarResult();
    }

    /**
     * @return QueryBuilder
     */
    private function getUsedScopesQueryBuilder()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Scope::class, 'scope')
            ->select('scope')
            ->innerJoin(
                $this->getEntityName(),
                'slug',
                Join::WITH,
                $qb->expr()->isMemberOf('scope', 'slug.scopes')
            );
        return $qb;
    }
}
