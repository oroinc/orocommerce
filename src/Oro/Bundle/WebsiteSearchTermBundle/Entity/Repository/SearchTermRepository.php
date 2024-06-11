<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Entity Repository for the Search Term
 */
class SearchTermRepository extends ServiceEntityRepository
{
    private string $delimiter;

    public function __construct(ManagerRegistry $registry, string $entityClass, string $delimiter)
    {
        parent::__construct($registry, $entityClass);

        $this->delimiter = $delimiter;
    }

    public function findSearchTermByScopes(string $phrase, array $scopes): ?SearchTerm
    {
        $qb = $this->createQueryBuilder('search_term');

        $qb
            ->leftJoin(
                'search_term.scopes',
                'scope'
            )
            ->where(
                $qb->expr()->eq(
                    ':phrase',
                    'ANYOF(STRING_TO_ARRAY(search_term.phrases, :delimiter))'
                )
            )
            ->setParameters([
                'phrase' => $phrase,
                'delimiter' => $this->delimiter,
            ]);

        $this->restrictByScopes($qb, $scopes);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findSearchTermWithPartialMatchByScopes(string $phrase, array $scopes): ?SearchTerm
    {
        $qb = $this->createQueryBuilder('search_term');

        $qb
            ->leftJoin(
                'search_term.scopes',
                'scope'
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->like(
                        'search_term.phrases',
                        ':partialMatchPhrase'
                    ),
                    $qb->expr()->eq('search_term.partialMatch', ':isPartialMatchAllowed')
                )
            )
            ->setParameters([
                'partialMatchPhrase' => '%' . $phrase . '%',
                'isPartialMatchAllowed' => true,
            ]);

        $this->restrictByScopes($qb, $scopes);
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
        $criteria->applyWhereWithPriority($qb, 'scope');

        return $qb->getQuery()->getResult();
    }

    private function getUsedScopesQueryBuilder(): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from(Scope::class, 'scope')
            ->select('scope')
            ->innerJoin(
                $this->getEntityName(),
                'search_term',
                Join::WITH,
                $qb->expr()->isMemberOf('scope', 'search_term.scopes')
            );

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param Scope[] $scopes
     *
     * @return void
     */
    private function restrictByScopes(QueryBuilder $qb, array $scopes): void
    {
        $scopesExpression = $qb->expr()->orX();
        foreach ($scopes as $index => $scope) {
            QueryBuilderUtil::checkIdentifier($index);

            $scopesExpression->add($qb->expr()->eq('scope', ':scope' . $index));
            $qb->setParameter(':scope' . $index, $scope);
        }
        $scopesExpression->add($qb->expr()->isNull('scope'));
        $qb->andWhere($scopesExpression);
    }
}
