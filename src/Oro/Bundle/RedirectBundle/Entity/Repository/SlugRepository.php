<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class SlugRepository extends EntityRepository
{
    /**
     * @param string $slug
     * @param SlugAwareInterface $restrictedEntity
     * @return Slug|null
     */
    public function findOneBySlugWithoutScopes($slug, SlugAwareInterface $restrictedEntity = null)
    {
        $qb = $this->createQueryBuilder('slug');

        $qb->leftJoin('slug.scopes', 'scopes', Join::WITH)
            ->where($qb->expr()->eq('slug.url', ':url'))
            ->andWhere($qb->expr()->isNull('scopes.id'))
            ->setParameter('url', $slug)
            ->setMaxResults(1);

        if ($restrictedEntity && $ids = $this->getEntitySlugIds($restrictedEntity)) {
            $qb->andWhere($qb->expr()->notIn('slug.id', ':ids'))
                ->setParameter('ids', $ids);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $pattern
     * @param SlugAwareInterface $restrictedEntity
     * @return array|string[]
     */
    public function findAllByPatternWithoutScopes($pattern, SlugAwareInterface $restrictedEntity = null)
    {
        $qb = $this->createQueryBuilder('slug');
        $qb->select('slug.url')
            ->leftJoin('slug.scopes', 'scopes', Join::WITH)
            ->where('slug.url LIKE :pattern')
            ->andWhere($qb->expr()->isNull('scopes.id'))
            ->setParameter('pattern', $pattern)
            ->orderBy('slug.id');

        if ($restrictedEntity && $ids = $this->getEntitySlugIds($restrictedEntity)) {
            $qb->andWhere($qb->expr()->notIn('slug.id', ':ids'))
                ->setParameter('ids', $ids);
        }

        return array_map(function ($item) {
            return $item['url'];
        }, $qb->getQuery()->getArrayResult());
    }

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
        $entityManager = $this->getEntityManager();

        $entityMetadata = $entityManager->getClassMetadata($entityClass);
        $mapping = $entityMetadata->getAssociationMapping('slugs');

        $entityIdField = $entityMetadata->getSingleIdentifierFieldName();
        $subQueryBuilder = $entityManager->getConnection()->createQueryBuilder();

        $joinColumn = reset($mapping['joinTable']['joinColumns']);
        $inverseJoinColumn = reset($mapping['joinTable']['inverseJoinColumns']);
        $subQueryBuilder
            ->select(sprintf('slugsJoinTable.%s', $inverseJoinColumn['name']))
            ->from($entityMetadata->getTableName(), 'entity')
            ->innerJoin(
                'entity',
                $mapping['joinTable']['name'],
                'slugsJoinTable',
                sprintf('entity.%s = slugsJoinTable.%s', $entityIdField, $joinColumn['name'])
            );

        $slugMetadata = $entityManager->getClassMetadata($this->_entityName);
        $slugIdField = $slugMetadata->getSingleIdentifierFieldName();

        $queryBuilder = $entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->delete($slugMetadata->getTableName())
            ->where($queryBuilder->expr()->in($slugIdField, $subQueryBuilder->getSQL()));

        $queryBuilder->execute();
    }

    /**
     * @param SlugAwareInterface $restrictedEntity
     * @return array|int[]
     */
    private function getEntitySlugIds(SlugAwareInterface $restrictedEntity)
    {
        $entitySlugIds = [];
        foreach ($restrictedEntity->getSlugs() as $entitySlug) {
            $entitySlugIds[] = $entitySlug->getId();
        }

        return $entitySlugIds;
    }
}
