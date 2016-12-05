<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\RedirectBundle\Entity\Slug;
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
            ->setParameter('url', $url)
            ->setMaxResults(1);

        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');

        $result = $qb->getQuery()->getOneOrNullResult();
        if ($result) {
            /** @var Slug $slug */
            $slug = $result[0];
            $matchedScopeId = $result['matchedScopeId'];
            if ($matchedScopeId || $slug->getScopes()->isEmpty()) {
                return $slug;
            }
        }

        return null;
    }
}
