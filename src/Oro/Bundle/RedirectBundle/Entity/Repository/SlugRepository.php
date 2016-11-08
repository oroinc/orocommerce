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
            ->where($qb->expr()->eq('slug.urlHash', ':urlHash'))
            ->andWhere($qb->expr()->eq('slug.url', ':url'))
            ->setParameter('urlHash', md5($url))
            ->setParameter('url', $url)
            ->setMaxResults(1);

        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');

        return $qb->getQuery()->getOneOrNullResult();
    }
}
