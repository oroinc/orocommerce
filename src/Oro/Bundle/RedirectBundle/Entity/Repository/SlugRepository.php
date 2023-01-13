<?php

namespace Oro\Bundle\RedirectBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\RedirectBundle\Entity\Hydrator\MatchingSlugHydrator;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface;
use Oro\Bundle\RedirectBundle\Helper\UrlParameterHelper;
use Oro\Bundle\RedirectBundle\Model\SlugQueryBuilderModifierInterface;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Repository for Slug entity
 */
class SlugRepository extends ServiceEntityRepository
{
    private SlugQueryBuilderModifierInterface $slugQueryBuilderModifier;

    public function setSlugQueryBuilderModifier(SlugQueryBuilderModifierInterface $slugQueryBuilderModifier): void
    {
        $this->slugQueryBuilderModifier = $slugQueryBuilderModifier;
    }

    public function getOneDirectUrlBySlugQueryBuilder(
        string $slug,
        SlugAwareInterface $restrictedEntity = null,
        ScopeCriteria $scopeCriteria = null
    ): QueryBuilder {
        $qb = $this->getSlugByUrlQueryBuilder($slug);
        $this->applyDirectUrlScopeCriteria($qb, $scopeCriteria);

        $qb->setMaxResults(1);
        $this->restrictByEntitySlugs($qb, $restrictedEntity);

        return $qb;
    }

    public function findAllDirectUrlsByPattern(
        string $pattern,
        SlugAwareInterface $restrictedEntity = null,
        ScopeCriteria $scopeCriteria = null
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from($this->getEntityName(), 'slug')
            ->select('slug.url')
            ->where('slug.url LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->orderBy('slug.id');

        $this->applyDirectUrlScopeCriteria($qb, $scopeCriteria);
        $this->restrictByEntitySlugs($qb, $restrictedEntity);
        $this->slugQueryBuilderModifier->modify($qb);

        $rawResult = $qb->getQuery()->getArrayResult();

        return \array_column($rawResult, 'url');
    }

    public function getSlugByUrlAndScopeCriteria(string $url, ScopeCriteria $scopeCriteria): ?Slug
    {
        $qb = $this->getSlugByUrlQueryBuilder($url);
        $qb->leftJoin('slug.scopes', 'scopes', Join::WITH)
            ->addSelect('scopes.id as matchedScopeId');

        return $this->getMatchingSlugForCriteria($qb, $scopeCriteria);
    }

    public function getSlugBySlugPrototypeAndScopeCriteria(string $slugPrototype, ScopeCriteria $scopeCriteria): ?Slug
    {
        $qb = $this->createQueryBuilder('slug');
        $this->applyDirectUrlScopeCriteria($qb);
        $qb->addSelect('scopes.id as matchedScopeId')
            ->andWhere($qb->expr()->eq('slug.slugPrototype', ':slugPrototype'))
            ->setParameter('slugPrototype', $slugPrototype);

        $this->slugQueryBuilderModifier->modify($qb);

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
     */
    public function getRawSlug(string $name, array $parameters, ?int $localizationId): ?array
    {
        $connection = $this->getEntityManager()->getConnection();

        $localizationIdSortOrder = 'DESC';
        if ($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $localizationIdSortOrder .= ' NULLS LAST';
        }
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
                    'parametersHash' => UrlParameterHelper::hashParams($parameters),
                    'routeName' => $name,
                    'routeParameters' => $parameters,
                    'localizationId' => $localizationId
                ],
                [
                    'parametersHash' => Types::STRING,
                    'routeName' => Types::STRING,
                    'routeParameters' => Types::ARRAY,
                    'localizationId' => Types::INTEGER
                ]
            )
            ->addOrderBy('slug.localization_id', $localizationIdSortOrder)
            ->addOrderBy('slug.id')
            ->setMaxResults(1);

        $result = $qb->execute()->fetch(\PDO::FETCH_ASSOC);
        if (false === $result) {
            $result = null;
        }

        return $result;
    }

    public function isSlugForRouteExists(string $routeName): bool
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('1')
            ->from('oro_redirect_slug', 'slug')
            ->leftJoin('slug', 'oro_slug_scope', 'scope', 'scope.slug_id = slug.id')
            ->where('scope.slug_id IS NULL')
            ->andWhere('slug.route_name = :routeName')
            ->setParameter('routeName', $routeName)
            ->setMaxResults(1);

        return (bool)$qb->execute()->fetch(\PDO::FETCH_ASSOC);
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
     * Find all most suitable scopes that fit the criteria.
     *
     * @param ScopeCriteria $criteria
     *
     * @return Scope[]
     */
    public function findMostSuitableUsedScopes(ScopeCriteria $criteria): array
    {
        $qb = $this->getUsedScopesQueryBuilder();
        $criteria->applyWhereWithPriorityForScopes($qb, 'scope');

        return $qb->getQuery()->getResult();
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

    private function getSlugByUrlQueryBuilder(string $url): QueryBuilder
    {
        $qb = $this->createQueryBuilder('slug');
        $qb
            ->where($qb->expr()->eq('slug.urlHash', ':urlHash'))
            ->andWhere($qb->expr()->eq('slug.url', ':url'))
            ->setParameter('url', $url)
            ->setParameter('urlHash', md5($url));

        $this->slugQueryBuilderModifier->modify($qb);

        return $qb;
    }

    private function getUsedScopesQueryBuilder(): QueryBuilder
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

    private function getMatchingSlugForCriteria(QueryBuilder $qb, ScopeCriteria $scopeCriteria): ?Slug
    {
        $scopeCriteria->applyToJoinWithPriority($qb, 'scopes');
        $qb->addOrderBy('slug.id');

        $result = $qb->getQuery()->getResult(MatchingSlugHydrator::NAME);
        if (!$result) {
            return null;
        }

        return reset($result);
    }

    private function restrictByEntitySlugs(QueryBuilder $qb, SlugAwareInterface $restrictedEntity = null): void
    {
        if ($restrictedEntity && count($restrictedEntity->getSlugs())) {
            $qb->andWhere($qb->expr()->notIn('slug', ':slugs'))
                ->setParameter('slugs', $restrictedEntity->getSlugs());
        }
    }

    private function applyDirectUrlScopeCriteria(QueryBuilder $qb, ScopeCriteria $scopeCriteria = null): void
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
