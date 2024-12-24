<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion;

/**
 * ORM Repository for Suggestion Entity
 */
class SuggestionRepository extends EntityRepository
{
    /**
     * @param int $organizationId
     * @param int $localizationId
     * @param array<array{phrase: string, words_count: int}> $phrases
     *
     * @return array{inserted: array<array{id: int, phrase: string}>}
     */
    public function saveSuggestions(int $organizationId, int $localizationId, array $phrases): array
    {
        $phrases = \array_values($phrases);
        if (empty($phrases)) {
            return [];
        }

        $inserted = $this->insertSuggestions($organizationId, $localizationId, $phrases);

        $skipped = $this->getSuggestIdsExceptInsertedIds(
            $organizationId,
            $localizationId,
            $phrases,
            array_column($inserted, 'id')
        );

        return compact('inserted', 'skipped');
    }

    /**
     * @param int $organizationId
     * @param int $localizationId
     * @param array<array{phrase: string, words_count: int}> $phrases
     *
     * @return array<array{id: int, phrase: string}>
     */
    private function insertSuggestions(int $organizationId, int $localizationId, array $phrases): array
    {
        if (empty($phrases)) {
            return [];
        }

        $values = [];
        $createdAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $params = [
            'createdAt' => $createdAt->format('Y-m-d H:i:s'),
            'localizationId' => $localizationId
        ];

        $types = [];

        foreach ($phrases as $index => $suggestion) {
            $values[] = \sprintf(
                "(%s, %d, %s, %s, %s)",
                ":phrase{$index}",
                $suggestion['words_count'],
                ':localizationId',
                $organizationId,
                ':createdAt'
            );

            $params["phrase{$index}"] = $suggestion['phrase'];

            $types[] = Types::STRING;
            $types[] = Types::INTEGER;
            $types[] = Types::DATE_IMMUTABLE;
        }

        $valuesInString = \implode(', ', $values);

        $query = <<<SQL
            INSERT INTO 
                oro_website_search_suggestion (phrase, words_count, localization_id, organization_id, created_at) 
            VALUES 
                {$valuesInString}
            ON CONFLICT DO NOTHING RETURNING id, phrase; 
        SQL;

        return $this->_em->getConnection()->executeQuery($query, $params, $types)->fetchAllAssociative();
    }

    /**
     * @param int $organizationId
     * @param int $localizationId
     * @param array<array{phrase: string, words_count: int}> $phrases
     * @param array<int> $insertedIds
     *
     * @return array<array{id: int, phrase: string}>
     */
    private function getSuggestIdsExceptInsertedIds(
        int $organizationId,
        int $localizationId,
        array $phrases,
        array $insertedIds
    ): array {
        $phrases = \array_column($phrases, 'phrase');
        if (empty($phrases)) {
            return [];
        }

        $qb = $this->createQueryBuilder('s');

        $qb
            ->select(['s.id', 's.phrase'])
            ->join('s.organization', 'o')
            ->where($qb->expr()->in('s.phrase', ':phrases'))
            ->andWhere($qb->expr()->eq('o.id', ':organizationId'))
            ->setParameter(':phrases', $phrases)
            ->setParameter(':organizationId', $organizationId);

        if (!empty($insertedIds)) {
            $qb
                ->andWhere($qb->expr()->notIn('s.id', ':ids'))
                ->setParameter('ids', $insertedIds);
        }

        $qb
            ->join('s.localization', 'l')
            ->andWhere($qb->expr()->eq('l.id', ':localizationId'))
            ->setParameter('localizationId', $localizationId);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array<int>
     */
    public function getSuggestionIdsWithEmptyProducts(): array
    {
        return $this->getSuggestionIdsWithEmptyProductsQB()
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    public function getSuggestionIdsWithEmptyProductsQB(): QueryBuilder
    {
        $subQueryBuilder = $this
            ->getEntityManager()
            ->getRepository(ProductSuggestion::class)
            ->createQueryBuilder('ps');

        $subQueryDql = $subQueryBuilder
            ->select('IDENTITY(ps.suggestion)')
            ->getQuery()
            ->getDQL();

        $queryBuilder = $this->createQueryBuilder('s');

        return $queryBuilder
            ->select(['s.id'])
            ->where($queryBuilder->expr()->notIn('s.id', $subQueryDql));
    }

    /**
     * @param QueryBuilder $qb
     * @param array<int> $localizations
     *
     * @return void
     */
    public function applyLocalizationRestrictions(QueryBuilder $qb, array $localizations): void
    {
        $qb
            ->innerJoin($qb->getRootAliases()[0] . '.localization', 'l')
            ->andWhere($qb->expr()->in('l.id', ':localizations'))
            ->setParameter('localizations', $localizations);
    }

    /**
     * @param array<int> $suggestionIds
     *
     * @return void
     */
    public function removeSuggestionsByIds(array $suggestionIds): void
    {
        $qb = $this->createQueryBuilder('s');

        $qb
            ->delete()
            ->where($qb->expr()->in('s.id', ':ids'))
            ->setParameter('ids', $suggestionIds)
            ->getQuery()
            ->execute();
    }
}
