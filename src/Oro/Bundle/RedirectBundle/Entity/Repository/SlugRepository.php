<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\RedirectBundle\Entity\Hydrator\MatchingSlugHydrator;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Repository for Slug entity
 */
class SlugRepository extends EntityRepository
{
    /**
     * @param string $url
     * @return QueryBuilder
     */
    public function getSlugByUrlQueryBuilder($url)
    {
        $qb = $this->createQueryBuilder('slug');

        $qb->where($qb->expr()->eq('slug.urlHash', ':urlHash'))
            ->andWhere($qb->expr()->eq('slug.url', ':url'))
            ->setParameter('url', $url)
            ->setParameter('urlHash', md5($url));

        return $qb;
    }

    /**
     * @param string $slug
     * @param SlugAwareInterface|null $restrictedEntity
     * @param ScopeCriteria|null $scopeCriteria
     * @return null|Slug
     */
    public function findOneDirectUrlBySlug(
        $slug,
        SlugAwareInterface $restrictedEntity = null,
        ScopeCriteria $scopeCriteria = null
    ) {
        $qb = $this->getSlugByUrlQueryBuilder($slug);
        $this->applyDirectUrlScopeCriteria($qb, $scopeCriteria);

        $qb->setMaxResults(1);
        $this->restrictByEntitySlugs($qb, $restrictedEntity);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $pattern
     * @param SlugAwareInterface|null $restrictedEntity
     * @param ScopeCriteria|null $scopeCriteria
     * @return array|\string[]
     */
    public function findAllDirectUrlsByPattern(
        $pattern,
        SlugAwareInterface $restrictedEntity = null,
        ScopeCriteria $scopeCriteria = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from($this->getEntityName(), 'slug')
            ->select('slug.url')
            ->where('slug.url LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->orderBy('slug.id');

        $this->applyDirectUrlScopeCriteria($qb, $scopeCriteria);
        $this->restrictByEntitySlugs($qb, $restrictedEntity);

        return array_map(
            function ($item) {
                return $item['url'];
            },
            $qb->getQuery()->getArrayResult()
        );
    }

    /**
     * @param string $url
     * @param ScopeCriteria $scopeCriteria
     * @return Slug|null
     */
    public function getSlugByUrlAndScopeCriteria($url, ScopeCriteria $scopeCriteria)
    {
        $qb = $this->getSlugByUrlQueryBuilder($url);

        $qb->leftJoin('slug.scopes', 'scopes', Join::WITH)
            ->addSelect('scopes.id as matchedScopeId');

        return $this->getMatchingSlugForCriteria($qb, $scopeCriteria);
    }

    /**
     * @param string $slugPrototype
     * @param ScopeCriteria $scopeCriteria
     * @return Slug|null
     */
    public function getSlugBySlugPrototypeAndScopeCriteria($slugPrototype, ScopeCriteria $scopeCriteria)
    {
        $qb = $this->createQueryBuilder('slug');
        $qb->leftJoin('slug.scopes', 'scopes', Join::WITH)
            ->addSelect('scopes.id as matchedScopeId')
            ->where($qb->expr()->eq('slug.slugPrototype', ':slugPrototype'))
            ->setParameter('slugPrototype', $slugPrototype);

        return $this->getMatchingSlugForCriteria($qb, $scopeCriteria);
    }

    /**
     * @param array $entityIds
     * @param ScopeCriteria|null $scopeCriteria
     * @return \array[]|\Iterator
     */
    public function getSlugDataForDirectUrls(array $entityIds, ScopeCriteria $scopeCriteria = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from($this->getEntityName(), 'slug')
            ->select(['slug.routeParameters', 'slug.url', 'slug.slugPrototype', 'localization.id as localization_id'])
            ->leftJoin('slug.localization', 'localization')
            ->andWhere($qb->expr()->in('slug.id', ':ids'))
            ->setParameter('ids', $entityIds)
            ->addOrderBy('slug.id', 'DESC')
            ->addOrderBy('localization.id', 'ASC');

        $this->applyDirectUrlScopeCriteria($qb, $scopeCriteria);

        $iterator = new BufferedQueryResultIterator($qb->getQuery());
        $iterator->setHydrationMode(Query::HYDRATE_ARRAY);
        $iterator->setReverse(true);

        return $iterator;
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
     * Doctrine cannot handle searching by "array" columns, therefore
     * we need a low-level query here.
     *
     * @param string $name
     * @param array $parameters
     * @param int $localizationId
     * @return null|array
     */
    public function getRawSlug($name, $parameters, $localizationId)
    {
        /** @var Connection $connection */
        $connection = $this->_em->getConnection();

        $localizationIdSortOrder = 'DESC';
        if ($this->_em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $localizationIdSortOrder .= ' NULLS LAST';
        }
        $hashParameters = UrlParameterHelper::hashParams($parameters);
        $qb = $connection->createQueryBuilder()
            ->select('slug.url', 'slug.slug_prototype')
            ->from('oro_redirect_slug', 'slug')
            ->leftJoin('slug', 'oro_slug_scope', 'scope', 'scope.slug_id = slug.id')
            ->where('scope.slug_id IS NULL')
            ->andWhere('slug.parameters_hash = :parametersHash')
            ->andWhere('slug.route_name = :routeName')
            ->andWhere('slug.route_parameters = :routeParameters')
            ->andWhere('slug.localization_id = :localizationId OR slug.localization_id IS NULL')
            ->setParameters(
                [
                    'parametersHash' => $hashParameters,
                    'routeName' => $name,
                    'routeParameters' => $parameters,
                    'localizationId' => $localizationId
                ],
                [
                    'parametersHash' => Type::STRING,
                    'routeName' => Type::STRING,
                    'routeParameters' => Type::TARRAY,
                    'localizationId' => Type::INTEGER
                ]
            )
            ->addOrderBy('slug.localization_id', $localizationIdSortOrder)
            ->setMaxResults(1);

        return $qb->execute()->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $routeName
     *
     * @return bool
     */
    public function isSlugForRouteExists(string $routeName): bool
    {
        /** @var Connection $connection */
        $connection = $this->_em->getConnection();

        $qb = $connection->createQueryBuilder();
        $qb->select('1')
            ->from('oro_redirect_slug', 'slug')
            ->leftJoin('slug', 'oro_slug_scope', 'scope', 'scope.slug_id = slug.id')
            ->where('scope.slug_id IS NULL')
            ->andWhere('slug.route_name = :routeName')
            ->setParameter('routeName', $routeName)
            ->setMaxResults(1);

        return (bool)$qb->execute()->fetch(\PDO::FETCH_ASSOC);
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

    /**
     * @return Scope[]|\Iterator
     */
    public function getUsedScopes()
    {
        $qb = $this->getUsedScopesQueryBuilder();

        return $qb->getQuery()->getResult();
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
     * Find most suitable scope attached to slug.
     *
     * @param ScopeCriteria $criteria
     * @return Scope|null
     */
    public function findMostSuitableUsedScope(ScopeCriteria $criteria)
    {
        $qb = $this->getUsedScopesQueryBuilder();
        $criteria->applyWhereWithPriority($qb, 'scope');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array|string[]
     */
    public function getUsedRoutes()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from($this->getEntityName(), 'slug')
            ->select('slug.routeName')
            ->distinct(true);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * @param string $routeName
     * @return int
     */
    public function getSlugsCountByRoute($routeName)
    {
        $entityCountQb = $this->createQueryBuilder('slug');

        return $entityCountQb->select('COUNT(slug)')
            ->where($entityCountQb->expr()->eq('slug.routeName', ':routeName'))
            ->setParameter('routeName', $routeName)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $routeName
     * @param int $page
     * @param int $perPage
     * @return array|int[]
     */
    public function getSlugIdsByRoute($routeName, $page, $perPage)
    {
        $qb = $this->createQueryBuilder('slug');
        $qb->select('slug.id')
            ->where($qb->expr()->eq('slug.routeName', ':routeName'))
            ->setParameter('routeName', $routeName)
            ->setFirstResult($page * $perPage)
            ->setMaxResults($perPage)
            ->orderBy('slug.id', 'ASC');

        return array_map('current', $qb->getQuery()->getArrayResult());
    }

    /**
     * @param QueryBuilder $qb
     * @param ScopeCriteria $scopeCriteria
     * @return null|Slug
     */
    private function getMatchingSlugForCriteria(QueryBuilder $qb, ScopeCriteria $scopeCriteria)
    {
        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');
        $result = $qb->getQuery()->getResult(MatchingSlugHydrator::NAME);
        if (!$result) {
            return null;
        }

        return reset($result);
    }

    /**
     * @param QueryBuilder $qb
     * @param SlugAwareInterface $restrictedEntity
     */
    private function restrictByEntitySlugs(QueryBuilder $qb, SlugAwareInterface $restrictedEntity = null)
    {
        if ($restrictedEntity && count($restrictedEntity->getSlugs())) {
            $qb->andWhere($qb->expr()->notIn('slug', ':slugs'))
                ->setParameter('slugs', $restrictedEntity->getSlugs());
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param ScopeCriteria|null $scopeCriteria
     */
    private function applyDirectUrlScopeCriteria(QueryBuilder $qb, ScopeCriteria $scopeCriteria = null)
    {
        if (null === $scopeCriteria) {
            $qb->leftJoin('slug.scopes', 'scopes', Join::WITH)
                ->andWhere($qb->expr()->isNull('scopes.id'));
        } else {
            $qb->innerJoin('slug.scopes', 'scopes', Join::WITH);
            $scopeCriteria->applyToJoin($qb, 'scopes');
        }
    }
}
