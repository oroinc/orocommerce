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
     * @param string $entityClass
     */
    public function deleteSlugAttachedToEntityByClass($entityClass)
    {
        $idsQb = $this->getEntityManager()->createQueryBuilder();
        $idsQb->select('s.id')
            ->from(Slug::class, 's')
            ->innerJoin($entityClass, 'e', Join::WITH, $idsQb->expr()->isMemberOf('s', 'e.slugs'));

        $deleteQb = $this->getEntityManager()->createQueryBuilder();
        $deleteQb->delete(Slug::class, 'slug')
            ->where($deleteQb->expr()->in('slug', $idsQb->getDQL()));

        $deleteQb->getQuery()->execute();
    }
}
