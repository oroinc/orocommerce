<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

/**
 * ORM Repository for ProductSuggestion Entity
 */
class ProductSuggestionRepository extends EntityRepository
{
    public function clearProductSuggestionsByProductIds(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $qb = $this->createQueryBuilder('ps');

        $qb
            ->delete()
            ->where($qb->expr()->in('ps.product', ':productIds'))
            ->setParameter('productIds', $productIds, Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<int, array<int>> $productIdsBySuggestionId
     *  [
     *      1 => [ // Suggestion ID
     *          1, 2, 3 // Product IDs
     *      ],
     *      // ...
     *  ]
     *
     * @return array<int> ProductSuggestion IDs
     */
    public function insertProductSuggestions(array $productIdsBySuggestionId): array
    {
        $values = [];

        foreach ($productIdsBySuggestionId as $suggestionId => $productIds) {
            foreach ($productIds as $productId) {
                $values[] = sprintf('(%d, %d)', (int)$suggestionId, (int)$productId);
            }
        }

        if (empty($values)) {
            return [];
        }

        $valuesInString = implode(', ', $values);

        $query = <<<SQL
            INSERT INTO
                oro_website_search_suggestion_product (suggestion_id, product_id)
            SELECT suggestion_id, product_id
            FROM (
                VALUES {$valuesInString}
            ) AS input(suggestion_id, product_id)
            WHERE EXISTS (
                SELECT 1 FROM oro_website_search_suggestion WHERE id = input.suggestion_id
            )
            ON CONFLICT DO NOTHING
            RETURNING id;
        SQL;

        return $this->_em->getConnection()->executeQuery($query)->fetchAllAssociative();
    }
}
